<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Paypal\Test\Unit\Model\Method\Checks;

use Magento\Paypal\Model\Method\Checks\SpecificationPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Paypal\Model\Billing\AgreementFactory as BillingAgreementFactory;
use Magento\Payment\Model\Checks\SpecificationInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Paypal\Model\ResourceModel\Billing\Agreement\Collection as BillingAgreementCollection;
use Magento\Paypal\Model\Billing\Agreement as BillingAgreement;

class SpecificationPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SpecificationPlugin
     */
    private $plugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var BillingAgreementFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $billingAgreementFactoryMock;

    /**
     * @var SpecificationInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $specificationMock;

    /**
     * @var MethodInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentMethodMock;

    /**
     * @var Quote|\PHPUnit\Framework\MockObject\MockObject
     */
    private $quoteMock;

    /**
     * @var BillingAgreementCollection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $billingAgreementCollectionMock;

    /**
     * @var BillingAgreement|\PHPUnit\Framework\MockObject\MockObject
     */
    private $billingAgreementMock;

    protected function setUp(): void
    {
        $this->billingAgreementFactoryMock = $this->getMockBuilder(BillingAgreementFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->specificationMock = $this->getMockBuilder(SpecificationInterface::class)
            ->getMockForAbstractClass();
        $this->paymentMethodMock = $this->getMockBuilder(MethodInterface::class)
            ->getMockForAbstractClass();
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerId'])
            ->getMock();
        $this->billingAgreementCollectionMock = $this->getMockBuilder(BillingAgreementCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->billingAgreementMock = $this->getMockBuilder(BillingAgreement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->plugin = $this->objectManagerHelper->getObject(
            SpecificationPlugin::class,
            [
                'agreementFactory' => $this->billingAgreementFactoryMock
            ]
        );
    }

    public function testAfterIsApplicableNotOriginallyApplicable()
    {
        $this->setExpectations('any', 'any');

        $this->assertFalse(
            $this->plugin->afterIsApplicable(
                $this->specificationMock,
                false,
                $this->paymentMethodMock,
                $this->quoteMock
            )
        );
    }

    public function testAfterIsApplicableNotAgreement()
    {
        $this->setExpectations('not_agreement', 'any');

        $this->assertTrue(
            $this->plugin->afterIsApplicable(
                $this->specificationMock,
                true,
                $this->paymentMethodMock,
                $this->quoteMock
            )
        );
    }

    public function testAfterIsApplicableNoCustomerId()
    {
        $this->setExpectations('paypal_billing_agreement', null);

        $this->assertFalse(
            $this->plugin->afterIsApplicable(
                $this->specificationMock,
                true,
                $this->paymentMethodMock,
                $this->quoteMock
            )
        );
    }

    /**
     * @param int $count
     *
     * @dataProvider afterIsApplicableDataProvider
     */
    public function testAfterIsApplicable($count)
    {
        $this->setExpectations('paypal_billing_agreement', 1);

        $this->billingAgreementFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($this->billingAgreementMock);
        $this->billingAgreementMock->expects(static::once())
            ->method('getAvailableCustomerBillingAgreements')
            ->with(1)
            ->willReturn($this->billingAgreementCollectionMock);
        $this->billingAgreementCollectionMock->expects(static::once())
            ->method('count')
            ->willReturn($count);

        $this->assertEquals(
            $count > 0,
            $this->plugin->afterIsApplicable($this->specificationMock, true, $this->paymentMethodMock, $this->quoteMock)
        );
    }

    /**
     * @return array
     */
    public function afterIsApplicableDataProvider()
    {
        return [[0], [1], [2]];
    }

    /**
     * Set expectations
     *
     * @param mixed $paymentMethodCode
     * @param mixed $customerId
     * @return void
     */
    private function setExpectations($paymentMethodCode, $customerId)
    {
        $this->paymentMethodMock->expects(static::any())
            ->method('getCode')
            ->willReturn($paymentMethodCode);
        $this->quoteMock->expects(static::any())
            ->method('getCustomerId')
            ->willReturn($customerId);
    }
}

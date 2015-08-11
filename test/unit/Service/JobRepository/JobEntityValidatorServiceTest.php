<?php
/**
 * @package: chapi
 *
 * @author:  msiebeneicher
 * @since:   2015-08-11
 *
 */

namespace unit\Service\JobRepository;


use Chapi\Entity\Chronos\JobEntity;
use Chapi\Exception\DatePeriodException;
use Chapi\Service\JobRepository\JobEntityValidatorService;
use Prophecy\Argument;

class JobEntityValidatorServiceTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Prophecy\Prophecy\ObjectProphecy */
    private $oDatePeriodFactory;

    public function setUp()
    {
        $this->oDatePeriodFactory = $this->prophesize('Chapi\Component\DatePeriod\DatePeriodFactoryInterface');

        $this->oDatePeriodFactory
            ->createDatePeriod(Argument::type('string'), Argument::type('string'))
            ->willReturn(
                new \DatePeriod(
                    new \DateTime('-1 day'),
                    new \DateInterval('PT10M'),
                    new \DateTime('+ 1 day')
                )
            )
        ;
    }

    public function testIsEntityValidScheduledSuccess()
    {
        $_oJobEntity = $this->getValidScheduledJobEntity();

        $this->oDatePeriodFactory
            ->createDatePeriod(Argument::type('string'), Argument::type('string'))
            ->shouldBeCalledTimes(1)
        ;

        $_oJobEntityValidatorService = new JobEntityValidatorService(
            $this->oDatePeriodFactory->reveal()
        );

        $this->assertTrue(
            $_oJobEntityValidatorService->isEntityValid($_oJobEntity)
        );
    }

    public function testIsEntityValidScheduledFailure()
    {
        $_oJobEntityValidatorService = new JobEntityValidatorService(
            $this->oDatePeriodFactory->reveal()
        );

        // -------------------------------------
        // test empty JobEntity
        // -------------------------------------
        $_oJobEntity = new JobEntity();
        $this->assertFalse(
            $_oJobEntityValidatorService->isEntityValid($_oJobEntity)
        );

        // -------------------------------------
        // test empty properties
        // -------------------------------------
        $_oJobEntity = $this->getValidScheduledJobEntity();
        $_oJobEntity->name = '';

        $this->assertFalse(
            $_oJobEntityValidatorService->isEntityValid($_oJobEntity)
        );

        $_oJobEntity = $this->getValidScheduledJobEntity();
        $_oJobEntity->command = '';
        $this->assertFalse(
            $_oJobEntityValidatorService->isEntityValid($_oJobEntity)
        );

        $_oJobEntity = $this->getValidScheduledJobEntity();
        $_oJobEntity->description = '';
        $this->assertFalse(
            $_oJobEntityValidatorService->isEntityValid($_oJobEntity)
        );

        $_oJobEntity = $this->getValidScheduledJobEntity();
        $_oJobEntity->owner = '';
        $this->assertFalse(
            $_oJobEntityValidatorService->isEntityValid($_oJobEntity)
        );

        $_oJobEntity = $this->getValidScheduledJobEntity();
        $_oJobEntity->ownerName = '';
        $this->assertFalse(
            $_oJobEntityValidatorService->isEntityValid($_oJobEntity)
        );

        // -------------------------------------
        // test not boolean properties
        // -------------------------------------
        $_oJobEntity = $this->getValidScheduledJobEntity();
        $_oJobEntity->async = 'false';
        $this->assertFalse(
            $_oJobEntityValidatorService->isEntityValid($_oJobEntity)
        );

        $_oJobEntity = $this->getValidScheduledJobEntity();
        $_oJobEntity->disabled = 'false';
        $this->assertFalse(
            $_oJobEntityValidatorService->isEntityValid($_oJobEntity)
        );

        $_oJobEntity = $this->getValidScheduledJobEntity();
        $_oJobEntity->softError = 'false';
        $this->assertFalse(
            $_oJobEntityValidatorService->isEntityValid($_oJobEntity)
        );

        $_oJobEntity = $this->getValidScheduledJobEntity();
        $_oJobEntity->highPriority = 'false';
        $this->assertFalse(
            $_oJobEntityValidatorService->isEntityValid($_oJobEntity)
        );

        $_oJobEntity = $this->getValidScheduledJobEntity();
        $_oJobEntity->parents = ['JobB'];
        $this->assertFalse(
            $_oJobEntityValidatorService->isEntityValid($_oJobEntity)
        );

        // -------------------------------------
        // test numeric properties
        // -------------------------------------
        $_oJobEntity = $this->getValidScheduledJobEntity();
        $_oJobEntity->retries = -1;
        $this->assertFalse(
            $_oJobEntityValidatorService->isEntityValid($_oJobEntity)
        );

        // -------------------------------------
        // new instance to test schedule failing with exception
        // -------------------------------------
        $this->oDatePeriodFactory
            ->createDatePeriod(Argument::type('string'), Argument::type('string'))
            ->willThrow(
                new DatePeriodException('test exception')
            )
        ;

        $_oJobEntityValidatorService = new JobEntityValidatorService(
            $this->oDatePeriodFactory->reveal()
        );

        $_oJobEntity = $this->getValidScheduledJobEntity();
        $_oJobEntity->schedule = 'notSupportedString';
        $this->assertFalse(
            $_oJobEntityValidatorService->isEntityValid($_oJobEntity)
        );

        // -------------------------------------
        // new instance to test schedule failing
        // -------------------------------------
        $this->oDatePeriodFactory
            ->createDatePeriod(Argument::type('string'), Argument::type('string'))
            ->willReturn(false)
        ;

        $_oJobEntityValidatorService = new JobEntityValidatorService(
            $this->oDatePeriodFactory->reveal()
        );

        $_oJobEntity = $this->getValidScheduledJobEntity();
        $_oJobEntity->schedule = 'notSupportedString';
        $this->assertFalse(
            $_oJobEntityValidatorService->isEntityValid($_oJobEntity)
        );
    }

    public function testIsEntityValidDependencySuccess()
    {
        $_oJobEntity = $this->getValidDependencyJobEntity();

        $this->oDatePeriodFactory
            ->createDatePeriod(Argument::type('string'), Argument::type('string'))
            ->shouldNotBeCalled()
        ;

        $_oJobEntityValidatorService = new JobEntityValidatorService(
            $this->oDatePeriodFactory->reveal()
        );

        $this->assertTrue(
            $_oJobEntityValidatorService->isEntityValid($_oJobEntity)
        );
    }

    public function testIsEntityValidDependencyFailure()
    {
        $_oJobEntityValidatorService = new JobEntityValidatorService(
            $this->oDatePeriodFactory->reveal()
        );

        // -------------------------------------
        // test !empty schedule property
        // -------------------------------------
        $_oJobEntity = $this->getValidDependencyJobEntity();
        $_oJobEntity->schedule = 'R/2015-09-01T02:00:00Z/P1M';

        $this->assertFalse(
            $_oJobEntityValidatorService->isEntityValid($_oJobEntity)
        );
    }

    private function getValidScheduledJobEntity()
    {
        $_oJobEntity = new JobEntity();

        $_oJobEntity->name = 'JobA';
        $_oJobEntity->command = 'echo test';
        $_oJobEntity->description = 'description';
        $_oJobEntity->owner = 'mail@address.com';
        $_oJobEntity->ownerName = 'ownerName';
        $_oJobEntity->schedule = 'R/2015-09-01T02:00:00Z/P1M';
        $_oJobEntity->scheduleTimeZone = 'Europe/Berlin';

        return $_oJobEntity;
    }

    private function getValidDependencyJobEntity()
    {
        $_oJobEntity = new JobEntity();

        $_oJobEntity->name = 'JobA';
        $_oJobEntity->command = 'echo test';
        $_oJobEntity->description = 'description';
        $_oJobEntity->owner = 'mail@address.com';
        $_oJobEntity->ownerName = 'ownerName';
        $_oJobEntity->parents = ['JobB'];

        return $_oJobEntity;
    }
}
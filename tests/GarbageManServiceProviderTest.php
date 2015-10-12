<?php

namespace Spinen\GarbageMan;

use ArrayAccess as Application;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Support\ServiceProvider;
use Mockery;
use Spinen\GarbageMan\Commands\PurgeCommand;

class GarbageManServiceProviderTest extends TestCase
{
    /**
     * @var Mockery\Mock
     */
    protected $application_mock;

    /**
     * @var Mockery\Mock
     */
    protected $events_mock;

    /**
     * @var Mockery\Mock
     */
    protected $purge_command_mock;

    /**
     * @var ServiceProvider
     */
    protected $service_provider;

    public function setUp()
    {
        parent::setUp();

        $this->setUpMocks();

        $this->service_provider = new GarbageManServiceProvider($this->application_mock);
    }

    private function setUpMocks()
    {
        $this->events_mock = Mockery::mock(Events::class);
        $this->events_mock->shouldReceive('listen')
                          ->withAnyArgs()
                          ->andReturnNull();

        $this->application_mock = Mockery::mock(Application::class);
        $this->application_mock->shouldReceive('offsetGet')
                               ->zeroOrMoreTimes()
                               ->with('events')
                               ->andReturn($this->events_mock);

        $this->purge_command_mock = Mockery::mock(PurgeCommand::class);
    }

    /**
     * @test
     * @group unit
     */
    public function it_can_be_constructed()
    {
        $this->assertInstanceOf(GarbageManServiceProvider::class, $this->service_provider);
    }

    /**
     * @test
     * @group unit
     */
    public function it_registers_the_purge_command_mock()
    {
        $this->application_mock->shouldReceive('make')
                               ->once()
                               ->with(PurgeCommand::class)
                               ->andReturn($this->purge_command_mock);

        $this->application_mock->shouldReceive('singleton')
                               ->once()
                               ->withArgs([
                                   'command.garbageman.purge',
                                   Mockery::on(function ($closure) {
                                       $this->assertInstanceOf(PurgeCommand::class, $closure($this->application_mock));

                                       return true;
                                   }),
                               ])
                               ->andReturnNull();

        $this->assertNull($this->service_provider->register());
    }

    /**
     * @test
     * @group unit
     */
    public function it_boots_the_service()
    {
        $this->assertNull($this->service_provider->boot());

        // NOTE: It would be nice to verify that the config got set.
    }
}

function config_path($file)
{
    return 'path/to/config/' . $file;
}


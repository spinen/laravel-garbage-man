<?php

namespace Spinen\GarbageMan\Commands;

use Carbon\Carbon;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use Spinen\GarbageMan\Commands\Stubs\PurgeCommandStub as PurgeCommand;
use Spinen\GarbageMan\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PurgeCommandTests extends TestCase
{
    /**
     * @var Mockery\Mock
     */
    protected $carbon_mock;

    /**
     * @var Mockery\Mock
     */
    protected $config_mock;

    /**
     * @var PurgeCommand
     */
    protected $command;

    /**
     * @var Mockery\Mock
     */
    protected $input_mock;

    /**
     * @var Mockery\Mock
     */
    protected $laravel_mock;

    /**
     * @var Mockery\Mock
     */
    protected $output_formatter_mock;

    /**
     * @var Mockery\Mock
     */
    protected $output_mock;

    public function setUp()
    {
        parent::setUp();

        $this->setUpMocks();

        $this->command = new PurgeCommand($this->carbon_mock);
        $this->command->setLaravel($this->laravel_mock);
        $this->command->setInput($this->input_mock);
        $this->command->setOutput($this->output_mock);
    }

    private function setUpMocks()
    {
        $this->carbon_mock = Mockery::mock(Carbon::class);
        $this->carbon_mock->shouldReceive('now')
                          ->once()
                          ->withNoArgs()
                          ->andReturnSelf();

        $this->config_mock = Mockery::mock(Config::class);

        $this->laravel_mock = Mockery::mock(Application::class);
        $this->laravel_mock->shouldReceive('make')
                           ->with('config')
                           ->andReturn($this->config_mock);

        $this->input_mock = Mockery::mock(InputInterface::class);

        $this->output_formatter_mock = Mockery::mock(OutputFormatterInterface::class);

        $this->output_mock = Mockery::mock(OutputInterface::class);
        $this->output_mock->shouldReceive('getFormatter')
                          ->andReturn($this->output_formatter_mock);
    }

    /**
     * @test
     * @group unit
     */
    public function it_can_be_constructed()
    {
        $this->assertInstanceOf(PurgeCommand::class, $this->command);
    }

    /**
     * @test
     * @group unit
     */
    public function it_writes_a_comment_if_there_are_no_models_configured()
    {
        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.schedule',
                              [],
                          ])
                          ->andReturn([]);

        $this->output_mock->shouldReceive('writeln')
                          ->once()
                          ->with('<comment>There were no models configured to purge.</comment>')
                          ->andReturnNull();

        $this->command->handle();
    }

    /**
     * @test
     * @group unit
     */
    public function it_warns_on_models_in_the_config_that_does_not_exists()
    {
        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.schedule',
                              [],
                          ])
                          ->andReturn([
                              'NoneExisting' => 14,
                          ]);

        $this->output_formatter_mock->shouldReceive('setStyle')
                                    ->once()
                                    ->withArgs([
                                        'warning',
                                        Mockery::any(),
                                    ])
                                    ->andReturnNull();

        $this->output_mock->shouldReceive('writeln')
                          ->once()
                          ->with('<warning>The model [NoneExisting] was not found.</warning>')
                          ->andReturnNull();

        $this->command->handle();
    }

    /**
     * @test
     * @group unit
     */
    public function it_errors_on_models_in_the_config_that_does_not_have_onlyTrashed()
    {
        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.schedule',
                              [],
                          ])
                          ->andReturn([
                              'NoOnlyTrashed' => 14,
                          ]);

        $this->output_mock->shouldReceive('writeln')
                          ->once()
                          ->with('<error>The model [NoOnlyTrashed] does not support soft deleting.</error>')
                          ->andReturnNull();

        $this->command->handle();
    }

    /**
     * @test
     * @group unit
     */
    public function it_errors_on_models_in_the_config_that_does_not_have_forceDelete()
    {
        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.schedule',
                              [],
                          ])
                          ->andReturn([
                              'NoForceDelete' => 14,
                          ]);

        $this->output_mock->shouldReceive('writeln')
                          ->once()
                          ->with('<error>The model [NoForceDelete] does not support soft deleting.</error>')
                          ->andReturnNull();

        $this->command->handle();
    }

    /**
     * @test
     * @group unit
     */
    public function it_deletes_expired_records_for_models_with_soft_delete()
    {
        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.schedule',
                              [],
                          ])
                          ->andReturn([
                              'ModelOne' => 14,
                              'ModelTwo' => 30,
                          ]);

        $carbon_one_mock = $this->carbon_mock;

        $carbon_two_mock = $this->carbon_mock;

        $this->carbon_mock->shouldReceive('copy')
                          ->twice()
                          ->withNoArgs()
                          ->andReturn($carbon_one_mock, $carbon_two_mock);

        $carbon_one_mock->shouldReceive('subDays')
                        ->once()
                        ->with(14)
                        ->andReturnSelf();

        $carbon_one_mock->shouldReceive('toIso8601String')
                        ->once()
                        ->withNoArgs()
                        ->andReturn(14);

        $carbon_two_mock->shouldReceive('subDays')
                        ->once()
                        ->with(30)
                        ->andReturnSelf();

        $carbon_two_mock->shouldReceive('toIso8601String')
                        ->once()
                        ->withNoArgs()
                        ->andReturn(30);

        $model_one_mock = Mockery::mock(Model::class);
        $model_one_mock->shouldReceive('where')
                       ->withArgs([
                           'deleted_at',
                           '<',
                           $carbon_one_mock,
                       ])
                       ->andReturnSelf();
        $model_one_mock->shouldReceive('onlyTrashed')
                       ->once()
                       ->withNoArgs()
                       ->andReturnSelf();
        $model_one_mock->shouldReceive('forceDelete')
                       ->once()
                       ->withNoArgs()
                       ->andReturn(1);

        $model_two_mock = Mockery::mock(Model::class);
        $model_two_mock->shouldReceive('where')
                       ->withArgs([
                           'deleted_at',
                           '<',
                           $carbon_two_mock,
                       ])
                       ->andReturnSelf();
        $model_two_mock->shouldReceive('onlyTrashed')
                       ->once()
                       ->withNoArgs()
                       ->andReturnSelf();
        $model_two_mock->shouldReceive('forceDelete')
                       ->once()
                       ->withNoArgs()
                       ->andReturn(0);

        $this->laravel_mock->shouldReceive('make')
                           ->once()
                           ->with('ModelOne')
                           ->andReturn($model_one_mock);

        $this->laravel_mock->shouldReceive('make')
                           ->once()
                           ->with('ModelTwo')
                           ->andReturn($model_two_mock);

        $this->output_mock->shouldReceive('writeln')
                          ->once()
                          ->with('<info>Purged 1 record(s) for ModelOne that was deleted before 14.</info>')
                          ->andReturnNull();

        $this->output_mock->shouldReceive('writeln')
                          ->once()
                          ->with('<info>Purged 0 record(s) for ModelTwo that was deleted before 30.</info>')
                          ->andReturnNull();

        $this->command->handle();
    }
}

$fake_models = [
    'ModelOne'      => [
        'forceDelete',
        'onlyTrashed',
    ],
    'ModelTwo'      => [
        'forceDelete',
        'onlyTrashed',
    ],
    'NoOnlyTrashed' => [
        'forceDelete',
    ],
    'NoForceDelete' => [
        'onlyTrashed',
    ],
];

function class_exists($class)
{
    global $fake_models;

    return in_array($class, array_keys($fake_models));
}

function method_exists($class, $method)
{
    global $fake_models;

    return in_array($method, $fake_models[$class]);
}

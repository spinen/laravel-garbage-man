<?php

namespace Spinen\GarbageMan\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\Writer as Log;
use Iterator as Collection;
use Mockery;
use ReflectionClass;
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
    protected $dispatcher_mock;

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
    protected $log_mock;

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

        $this->command = new PurgeCommand($this->carbon_mock, $this->dispatcher_mock, $this->log_mock);
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

        $this->dispatcher_mock = Mockery::mock(Dispatcher::class);

        $this->laravel_mock = Mockery::mock(Application::class);
        $this->laravel_mock->shouldReceive('make')
                           ->with('config')
                           ->andReturn($this->config_mock);

        $this->log_mock = Mockery::mock(Log::class);

        $this->input_mock = Mockery::mock(InputInterface::class);

        $this->output_formatter_mock = Mockery::mock(OutputFormatterInterface::class);

        $this->output_mock = Mockery::mock(OutputInterface::class);
        $this->output_mock->shouldReceive('getFormatter')
                          ->andReturn($this->output_formatter_mock);
    }

    /**
     * Pad the number of arguments needed for console output
     *
     * In Laravel 5.2, they added the verbosity to the writln calls, so we need to account for that parameter in version
     * 5.2, but not in 5.1. I really don't like this solution, because we are a little too coupled to the Command class
     * which we do not own, but I really cannot come up with a better way to assert that the correct info is being
     * sent to the console.
     *
     * @see https://github.com/laravel/framework/commit/2f167031ca7d9660d3524d16cbba24eae5c759d7
     *
     * @param $line
     *
     * @return array
     */
    private function checkVerbosity($line)
    {
        // Since we are "mocking out" method_exist below, then try to reflect the method
        $reflection = new ReflectionClass(PurgeCommand::class);

       try {
           $reflection->getMethod('parseVerbosity');

           return [
               $line,
               Mockery::any(),
           ];
       } catch (Exception $e){
           return [
               $line,
           ];
       }
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
                              'garbageman.fire_purge_events',
                              false,
                          ])
                          ->andReturn(false);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.logging_level',
                              [
                                  'console' => 6,
                                  'log'     => 6,
                              ],
                          ])
                          ->andReturn([
                              'console' => 6,
                              'log'     => 6,
                          ]);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.schedule',
                              [],
                          ])
                          ->andReturn([]);

        $this->log_mock->shouldReceive('notice')
                       ->once()
                       ->with('There were no models configured to purge.')
                       ->andReturnNull();

        $this->output_mock->shouldReceive('writeln')
                          ->once()
                          ->withArgs($this->checkVerbosity('<comment>There were no models configured to purge.</comment>'))
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
                              'garbageman.fire_purge_events',
                              false,
                          ])
                          ->andReturn(false);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.logging_level',
                              [
                                  'console' => 6,
                                  'log'     => 6,
                              ],
                          ])
                          ->andReturn([
                              'console' => 6,
                              'log'     => 6,
                          ]);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.schedule',
                              [],
                          ])
                          ->andReturn([
                              'NoneExisting' => 14,
                          ]);

        // Newer versions of laravel style, so give it if not set
        $this->output_formatter_mock->shouldReceive('hasStyle')
                                    ->with('warning')
                                    ->andReturn(false);

        $this->output_formatter_mock->shouldReceive('setStyle')
                                    ->once()
                                    ->withArgs([
                                        'warning',
                                        Mockery::any(),
                                    ])
                                    ->andReturnNull();

        $this->log_mock->shouldReceive('warning')
                       ->once()
                       ->with('The model [NoneExisting] was not found.')
                       ->andReturnNull();

        $this->output_mock->shouldReceive('writeln')
                          ->once()
                          ->withArgs($this->checkVerbosity('<warning>The model [NoneExisting] was not found.</warning>'))
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
                              'garbageman.fire_purge_events',
                              false,
                          ])
                          ->andReturn(false);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.logging_level',
                              [
                                  'console' => 6,
                                  'log'     => 6,
                              ],
                          ])
                          ->andReturn([
                              'console' => 6,
                              'log'     => 6,
                          ]);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.schedule',
                              [],
                          ])
                          ->andReturn([
                              'NoOnlyTrashed' => 14,
                          ]);

        $this->log_mock->shouldReceive('error')
                       ->once()
                       ->with('The model [NoOnlyTrashed] does not support soft deleting.')
                       ->andReturnNull();

        $this->output_mock->shouldReceive('writeln')
                          ->once()
                          ->withArgs($this->checkVerbosity('<error>The model [NoOnlyTrashed] does not support soft deleting.</error>'))
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
                              'garbageman.fire_purge_events',
                              false,
                          ])
                          ->andReturn(false);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.logging_level',
                              [
                                  'console' => 6,
                                  'log'     => 6,
                              ],
                          ])
                          ->andReturn([
                              'console' => 6,
                              'log'     => 6,
                          ]);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.schedule',
                              [],
                          ])
                          ->andReturn([
                              'NoForceDelete' => 14,
                          ]);

        $this->log_mock->shouldReceive('error')
                       ->once()
                       ->with('The model [NoForceDelete] does not support soft deleting.')
                       ->andReturnNull();

        $this->output_mock->shouldReceive('writeln')
                          ->once()
                          ->withArgs($this->checkVerbosity('<error>The model [NoForceDelete] does not support soft deleting.</error>'))
                          ->andReturnNull();

        $this->command->handle();
    }

    /**
     * @test
     * @group unit
     */
    public function it_deletes_all_expired_records_for_models_with_soft_delete_when_not_configured_to_fire_events()
    {
        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.fire_purge_events',
                              false,
                          ])
                          ->andReturn(false);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.logging_level',
                              [
                                  'console' => 6,
                                  'log'     => 6,
                              ],
                          ])
                          ->andReturn([
                              'console' => 6,
                              'log'     => 6,
                          ]);

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

        $builder_one_mock = Mockery::mock(Builder::class);
        $builder_one_mock->shouldReceive('onlyTrashed')
                         ->once()
                         ->withNoArgs()
                         ->andReturnSelf();
        $builder_one_mock->shouldReceive('forceDelete')
                         ->once()
                         ->withNoArgs()
                         ->andReturn(1);

        $model_one_mock = Mockery::mock(Model::class);
        $model_one_mock->shouldReceive('where')
                       ->withArgs([
                           'deleted_at',
                           '<',
                           $carbon_one_mock,
                       ])
                       ->andReturn($builder_one_mock);

        $builder_two_mock = Mockery::mock(Builder::class);
        $builder_two_mock->shouldReceive('onlyTrashed')
                         ->once()
                         ->withNoArgs()
                         ->andReturnSelf();
        $builder_two_mock->shouldReceive('forceDelete')
                         ->once()
                         ->withNoArgs()
                         ->andReturn(0);

        $model_two_mock = Mockery::mock(Model::class);
        $model_two_mock->shouldReceive('where')
                       ->withArgs([
                           'deleted_at',
                           '<',
                           $carbon_two_mock,
                       ])
                       ->andReturn($builder_two_mock);

        $this->laravel_mock->shouldReceive('make')
                           ->once()
                           ->with('ModelOne')
                           ->andReturn($model_one_mock);

        $this->laravel_mock->shouldReceive('make')
                           ->once()
                           ->with('ModelTwo')
                           ->andReturn($model_two_mock);

        $this->log_mock->shouldReceive('info')
                       ->twice()
                       ->with('Deleting all the records in a single query statement.')
                       ->andReturnNull();

        $this->output_mock->shouldReceive('writeln')
                          ->twice()
                          ->withArgs($this->checkVerbosity('<info>Deleting all the records in a single query statement.</info>'))
                          ->andReturnNull();

        $this->log_mock->shouldReceive('info')
                       ->once()
                       ->with('Purged 1 record(s) for ModelOne that was deleted before 14.')
                       ->andReturnNull();

        $this->output_mock->shouldReceive('writeln')
                          ->once()
                          ->withArgs($this->checkVerbosity('<info>Purged 1 record(s) for ModelOne that was deleted before 14.</info>'))
                          ->andReturnNull();

        $this->log_mock->shouldReceive('info')
                       ->once()
                       ->with('Purged 0 record(s) for ModelTwo that was deleted before 30.')
                       ->andReturnNull();

        $this->output_mock->shouldReceive('writeln')
                          ->once()
                          ->withArgs($this->checkVerbosity('<info>Purged 0 record(s) for ModelTwo that was deleted before 30.</info>'))
                          ->andReturnNull();

        $this->command->handle();
    }

    /**
     * @test
     * @group unit
     */
    public function it_deletes_each_expired_record_for_models_and_throws_events_with_soft_delete_when_configured_to_fire_events(
    )
    {
        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.fire_purge_events',
                              false,
                          ])
                          ->andReturn(true);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.logging_level',
                              [
                                  'console' => 6,
                                  'log'     => 6,
                              ],
                          ])
                          ->andReturn([
                              'console' => 7,
                              'log'     => 7,
                          ]);

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

        $record_one_mock = Mockery::mock(Model::class);
        $record_one_mock->shouldReceive('forceDelete')
                        ->once()
                        ->withNoArgs()
                        ->andReturnNull();

        $collection_one_mock = Mockery::mock(Collection::class);
        $this->mockArrayIterator($collection_one_mock, [$record_one_mock]);
        $collection_one_mock->shouldReceive('count')
                            ->once()
                            ->withNoArgs()
                            ->andReturn(1);

        $builder_one_mock = Mockery::mock(Builder::class);
        $builder_one_mock->shouldReceive('onlyTrashed')
                         ->once()
                         ->withNoArgs()
                         ->andReturnSelf();
        $builder_one_mock->shouldReceive('get')
                         ->once()
                         ->withNoArgs()
                         ->andReturn($collection_one_mock);

        $model_one_mock = Mockery::mock(Model::class);
        $model_one_mock->shouldReceive('where')
                       ->withArgs([
                           'deleted_at',
                           '<',
                           $carbon_one_mock,
                       ])
                       ->andReturn($builder_one_mock);

        $collection_two_mock = Mockery::mock(Collection::class);
        $this->mockArrayIterator($collection_two_mock, []);
        $collection_two_mock->shouldReceive('count')
                            ->once()
                            ->withNoArgs()
                            ->andReturn(0);

        $builder_two_mock = Mockery::mock(Builder::class);
        $builder_two_mock->shouldReceive('onlyTrashed')
                         ->once()
                         ->withNoArgs()
                         ->andReturnSelf();
        $builder_two_mock->shouldReceive('get')
                         ->once()
                         ->withNoArgs()
                         ->andReturn($collection_two_mock);

        $model_two_mock = Mockery::mock(Model::class);
        $model_two_mock->shouldReceive('where')
                       ->withArgs([
                           'deleted_at',
                           '<',
                           $carbon_two_mock,
                       ])
                       ->andReturn($builder_two_mock);

        $this->laravel_mock->shouldReceive('make')
                           ->once()
                           ->with('ModelOne')
                           ->andReturn($model_one_mock);

        $this->laravel_mock->shouldReceive('make')
                           ->once()
                           ->with('ModelTwo')
                           ->andReturn($model_two_mock);

        $this->dispatcher_mock->shouldReceive('until')
                              ->once()
                              ->withArgs([
                                  'garbageman.purging: ModelOne',
                                  Mockery::any(),
                              ])
                              ->andReturnNull();

        $this->dispatcher_mock->shouldReceive('until')
                              ->once()
                              ->withArgs([
                                  'garbageman.purged: ModelOne',
                                  Mockery::any(),
                              ])
                              ->andReturnNull();

        $this->log_mock->shouldReceive('info')
                       ->twice()
                       ->with('Deleting each record separately and firing events.')
                       ->andReturnNull();

        $this->output_mock->shouldReceive('writeln')
                          ->twice()
                          ->withArgs($this->checkVerbosity('<info>Deleting each record separately and firing events.</info>'))
                          ->andReturnNull();

        $this->log_mock->shouldReceive('debug')
                       ->once()
                       ->with('Firing event [garbageman.purging: ModelOne] with method [until]')
                       ->andReturnNull();

        $this->output_mock->shouldReceive('writeln')
                          ->once()
                          ->withArgs($this->checkVerbosity('Firing event [garbageman.purging: ModelOne] with method [until]'))
                          ->andReturnNull();

        $this->log_mock->shouldReceive('debug')
                       ->once()
                       ->with('Firing event [garbageman.purged: ModelOne] with method [until]')
                       ->andReturnNull();

        $this->output_mock->shouldReceive('writeln')
                          ->once()
                          ->withArgs($this->checkVerbosity('Firing event [garbageman.purged: ModelOne] with method [until]'))
                          ->andReturnNull();

        $this->log_mock->shouldReceive('info')
                       ->once()
                       ->with('Purged 1 record(s) for ModelOne that was deleted before 14.')
                       ->andReturnNull();

        $this->output_mock->shouldReceive('writeln')
                          ->once()
                          ->withArgs($this->checkVerbosity('<info>Purged 1 record(s) for ModelOne that was deleted before 14.</info>'))
                          ->andReturnNull();

        $this->log_mock->shouldReceive('info')
                       ->once()
                       ->with('Purged 0 record(s) for ModelTwo that was deleted before 30.')
                       ->andReturnNull();

        $this->output_mock->shouldReceive('writeln')
                          ->once()
                          ->withArgs($this->checkVerbosity('<info>Purged 0 record(s) for ModelTwo that was deleted before 30.</info>'))
                          ->andReturnNull();

        $this->command->handle();
    }

    /**
     * @test
     * @group unit
     */
    public function it_defaults_to_log_even_if_the_configs_dont_have_needed_key()
    {
        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.fire_purge_events',
                              false,
                          ])
                          ->andReturn(false);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.logging_level',
                              [
                                  'console' => 6,
                                  'log'     => 6,
                              ],
                          ])
                          ->andReturn([
                              'bad_console' => 6,
                              'bad_log'     => 6,
                          ]);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.schedule',
                              [],
                          ])
                          ->andReturn([]);

        $this->log_mock->shouldReceive('notice')
                       ->once()
                       ->with('There were no models configured to purge.')
                       ->andReturnNull();

        $this->output_mock->shouldReceive('writeln')
                          ->once()
                          ->withArgs($this->checkVerbosity('<comment>There were no models configured to purge.</comment>'))
                          ->andReturnNull();

        $this->command->handle();
    }

    /**
     * @test
     * @group unit
     */
    public function it_does_not_log_if_alert_is_higher_than_allowed()
    {
        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.fire_purge_events',
                              false,
                          ])
                          ->andReturn(false);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.logging_level',
                              [
                                  'console' => 6,
                                  'log'     => 6,
                              ],
                          ])
                          ->andReturn([
                              'console' => 0,
                              'log'     => 6,
                          ]);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs([
                              'garbageman.schedule',
                              [],
                          ])
                          ->andReturn([]);

        $this->log_mock->shouldReceive('notice')
                       ->once()
                       ->with('There were no models configured to purge.')
                       ->andReturnNull();

        $this->output_mock->shouldReceive('writeln')
                          ->never()
                          ->withAnyargs();

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

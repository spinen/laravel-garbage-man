<?php

namespace Spinen\GarbageMan\Commands\Stubs;

use Spinen\GarbageMan\Commands\PurgeCommand;

/**
 * Class PurgeCommandStub
 *
 * Wrapper over the class to allow setting some properties for testing.
 */
class PurgeCommandStub extends PurgeCommand
{
    /**
     * Set the input.
     *
     *
     * @return $this
     */
    public function setInput($input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * Set the output.
     *
     *
     * @return $this
     */
    public function setOutput($output)
    {
        $this->output = $output;

        return $this;
    }
}

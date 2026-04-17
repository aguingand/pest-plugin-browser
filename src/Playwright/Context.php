<?php

declare(strict_types=1);

namespace Pest\Browser\Playwright;

use Exception;
use Generator;

/**
 * @internal
 */
final class Context
{
    use Concerns\InteractsWithPlaywright;

    /**
     * Indicates whether the browser context is closed.
     */
    private bool $closed = false;

    /**
     * Indicates whether tracing has been started.
     */
    private bool $tracingStarted = false;

    /**
     * Creates a new context instance.
     */
    public function __construct(
        private readonly Browser $browser,
        private readonly string $guid,
        private readonly string $tracingGuid,
    ) {
        //
    }

    /**
     * Gets the browser instance.
     */
    public function browser(): Browser
    {
        return $this->browser;
    }

    /**
     * Creates a new page in the context.
     */
    public function newPage(): Page
    {
        $response = Client::instance()->execute($this->guid, 'newPage');

        $frameGuid = '';
        $pageGuid = '';

        /** @var array{method: string|null, params: array{type: string|null, guid: string, initializer: array{url: string}}, result: array{page: array{guid: string|null}}} $message */
        foreach ($response as $message) {
            if (isset($message['method']) && $message['method'] === '__create__' && (isset($message['params']['type']) && $message['params']['type'] === 'Frame')) {
                $frameGuid = $message['params']['guid'];
            }

            if (isset($message['result']['page']['guid'])) {
                $pageGuid = $message['result']['page']['guid'];
            }
        }

        return new Page($this, $pageGuid, $frameGuid);
    }

    /**
     * Closes the browser context.
     */
    public function close(): void
    {
        if ($this->browser->isClosed() || $this->closed) {
            return;
        }

        if ($this->tracingStarted) {
            $this->stopTracing();
        }

        try {
            $response = $this->sendMessage('close');
            $this->processVoidResponse($response);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'has been closed')) {
                return;
            }

            throw $e;
        }

        $this->closed = true;
    }

    /**
     * Checks if the browser context is closed.
     */
    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * Starts tracing for the context.
     */
    public function startTracing(): void
    {
        $response = Client::instance()->execute($this->tracingGuid, 'tracingStart', [
            'screenshots' => true,
            'snapshots' => true,
        ]);
        $this->processVoidResponse($response);

        $chunkResponse = Client::instance()->execute($this->tracingGuid, 'tracingStartChunk');
        $this->processVoidResponse($chunkResponse);

        $this->tracingStarted = true;
    }

    /**
     * Saves current trace to the given path.
     */
    public function saveTrace(string $path): void
    {
        $response = Client::instance()->execute($this->tracingGuid, 'tracingStopChunk', ['mode' => 'archive']);

        $artifactGuid = $this->processArtifactGuidResponse($response);

        if ($artifactGuid !== null) {
            $saveResponse = Client::instance()->execute($artifactGuid, 'saveAs', ['path' => $path]);
            $this->processVoidResponse($saveResponse);

            $deleteResponse = Client::instance()->execute($artifactGuid, 'delete');
            $this->processVoidResponse($deleteResponse);
        }

        $chunkResponse = Client::instance()->execute($this->tracingGuid, 'tracingStartChunk');
        $this->processVoidResponse($chunkResponse);
    }

    /**
     * Stops tracing.
     */
    public function stopTracing(): void
    {
        $chunkResponse = Client::instance()->execute($this->tracingGuid, 'tracingStopChunk', ['mode' => 'discard']);
        $this->processVoidResponse($chunkResponse);

        $stopResponse = Client::instance()->execute($this->tracingGuid, 'tracingStop');
        $this->processVoidResponse($stopResponse);

        $this->tracingStarted = false;
    }

    /**
     * Adds a script which will be evaluated.
     */
    public function addInitScript(string $script): self
    {
        $response = $this->sendMessage('addInitScript', ['source' => $script]);
        $this->processVoidResponse($response);

        return $this;
    }

    /**
     * Process response to extract artifact GUID.
     */
    private function processArtifactGuidResponse(Generator $response): ?string
    {
        /** @var array{result?: array{artifact?: array{guid?: string}}} $message */
        foreach ($response as $message) {
            if (isset($message['result']['artifact']['guid'])) {
                return $message['result']['artifact']['guid'];
            }
        }

        return null;
    }
}

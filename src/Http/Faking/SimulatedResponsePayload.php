<?php declare(strict_types=1);

namespace Saloon\Http\Faking;

use Closure;
use Saloon\Http\PendingRequest;
use Saloon\Repositories\ArrayStore;
use Saloon\Contracts\Body\BodyRepository;
use Saloon\Repositories\Body\JsonBodyRepository;
use Saloon\Exceptions\DirectoryNotFoundException;
use Saloon\Repositories\Body\StringBodyRepository;
use Throwable;

class SimulatedResponsePayload
{
    /**
     * HTTP Status Code
     *
     * @var int
     */
    protected int $status;

    /**
     * Headers
     *
     * @var ArrayStore
     */
    protected ArrayStore $headers;

    /**
     * Request Body
     *
     * @var BodyRepository
     */
    protected BodyRepository $body;

    /**
     * Exception Closure
     *
     * @var Closure|null
     */
    protected ?Closure $responseException = null;

    /**
     * Create a new mock response
     *
     * @param int $status
     * @param array|string $body
     * @param array $headers
     */
    public function __construct(int $status = 200, array|string $body = [], array $headers = [])
    {
        $this->status = $status;
        $this->body = is_array($body) ? new JsonBodyRepository($body) : new StringBodyRepository($body);
        $this->headers = new ArrayStore($headers);
    }

    /**
     * Create a new mock response
     *
     * @param mixed $body
     * @param int $status
     * @param array $headers
     * @return static
     */
    public static function make(int $status = 200, mixed $body = [], array $headers = []): static
    {
        return new static($status, $body, $headers);
    }

    /**
     * Create a new mock response from a fixture
     *
     * @param string $name
     * @return Fixture
     * @throws DirectoryNotFoundException
     */
    public static function fixture(string $name): Fixture
    {
        return new Fixture($name);
    }

    /**
     * Get the status from the responses
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Get the headers
     *
     * @return ArrayStore
     */
    public function getHeaders(): ArrayStore
    {
        return $this->headers;
    }

    /**
     * Get the response body
     *
     * @return BodyRepository
     */
    public function getBody(): BodyRepository
    {
        return $this->body;
    }

    /**
     * Get the formatted body on the response.
     *
     * @return string
     */
    public function getBodyAsString(): string
    {
        return (string)$this->body;
    }

    /**
     * Throw an exception on the request.
     *
     * @param Closure|Throwable $value
     * @return $this
     */
    public function throw(Closure|Throwable $value): static
    {
        $closure = $value instanceof Throwable ? static fn () => $value : $value;

        $this->responseException = $closure;

        return $this;
    }

    /**
     * Checks if the response throws an exception.
     *
     * @return bool
     */
    public function throwsException(): bool
    {
        return $this->responseException instanceof Closure;
    }

    /**
     * Invoke the exception.
     *
     * @param PendingRequest $pendingRequest
     * @return Throwable|null
     */
    public function getException(PendingRequest $pendingRequest): ?Throwable
    {
        if (is_null($this->responseException)) {
            return null;
        }

        return call_user_func($this->responseException, $pendingRequest);
    }
}

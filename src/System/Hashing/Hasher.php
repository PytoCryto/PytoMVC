<?php

namespace PytoMVC\System\Hashing;

use RuntimeException;
use InvalidArgumentException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class Hasher
{
    /**
     * The hashing method defined in the app.php configuration file
     * 
     * @var string
     */
    protected $method;

    /**
     * This is the work factor for the bcrypt hashing method if used
     * 
     * @var int
     */
    protected $cost;

    public function __construct(ConfigRepository $config)
    {
        $this->setMethod($config->get('app.hash.method'));

        $this->setCost($config->get('app.hash.cost', 10));
    }

    /**
     * Set the hashing method
     * 
     * @param  string $value 
     * @return void
     */
    protected function setMethod($value)
    {
        if ($this->isHashMethodValid($value)) {
            $this->method = $value;
        }
    }

    /**
     * Set the work factor for bcrypt
     * 
     * @param  int $value 
     * @return void
     */
    protected function setCost($value)
    {
        $this->cost = $value;
    }

    /**
     * Get the method
     * 
     * @return string
     */
    protected function getMethod()
    {
        return $this->method;
    }

    /**
     * Get the cost
     * 
     * @return int
     */
    protected function getCost()
    {
        return $this->cost;
    }

    /**
     * Determine if the hashing method is bcrypt
     * 
     * @return bool
     */
    protected function isBcrypt()
    {
        return $this->getMethod() == 'bcrypt';
    }

    /**
     * Create a hash from the given value
     * 
     * @param  string $value 
     * @param  array $options 
     * @return string
     * 
     * @throws \RuntimeException
     */
    public function make($value, array $options = [])
    {
        if (! $this->isBcrypt()) {
            return call_user_func($this->getMethod(), $value);
        }

        $hash = password_hash($value, PASSWORD_BCRYPT, [
            'cost' => $this->getOptions($options)
        ]);

        if ($hash === false) {
            throw new RuntimeException('Bcrypt hashing not supported.');
        }

        return $hash;
    }

    /**
     * Check if the given value matches the hash
     * 
     * @param  string $value 
     * @param  string $hash 
     * @return bool
     */
    public function check($value, $hash)
    {
        if (strlen($hash) === 0) {
            return false;
        }

        if (! $this->isBcrypt()) {
            return call_user_func($this->getMethod(), $value) == $hash;
        }

        return password_verify($value, $hash);
    }

    /**
     * Check if the hashing method is valid
     * 
     * @param  string $method 
     * @return bool
     * 
     * @throws \InvalidArgumentException
     */
    protected function isHashMethodValid($method)
    {
        if (! in_array($method, $allowed = ['bcrypt', 'sha1', 'sha256', 'md5'])) {
            throw new InvalidArgumentException("Hash method ({$method}) is not valid. Allowed methods: " . join(',', $allowed));
        }

        return true;
    }

    /**
     * Get the options for bcrypt
     * 
     * @param  array $options 
     * @return type
     */
    protected function getOptions(array $options = [])
    {
        return isset($options['cost']) ? $options['cost'] : $this->getCost();
    }
}

<?php
/**
 * Class SimpleDI
 *
 * @author Hendrik Legge <hendrik.legge@themepoint.de>
 * @version 1.0.0
 * @package simpledi.core
 * @license MIT
 *
 * @example
 * $car = SmartDI::autowire(Car::class, [
 *      'wheels' => 4,
 *      'fuel' => 'Petrol'
 * ], [
 *      'resolveInjectedClasses' => true
 * ]);
 *
 * @options
 * calledMethod: Method which is called on class object (default: __construct)
 * resolveInjectedClasses: Define if classes which injected to called object will be resolved by SimpleDI, too
 * useOptionsForInjectedClasses: Define if SimpleDI options of called object well be applied to injected classes
 *
 */

class SimpleDI
{
    /**
     * @param string $class
     * @param array $container
     * @param array $options
     * @return mixed
     * @throws Exception
     */
    public static function autowire(string $class, array $container = [], array $options = [])
    {
        return (new self($class, $container, $options))->resolve()->getResolved();
    }

    private $class;
    private $container;
    private $options;
    private $resolved;

    /**
     * @var \ReflectionClass
     */
    private $reflectionClass;

    /**
     * @var \ReflectionMethod
     */
    private $calledMethod;

    public function __construct(string $class, array $container = [], array $options = [])
    {
        $this->class = $class;
        $this->container = $container;
        $this->options = $this->resolveOptions($options);
    }

    public function resolve(): self
    {
        if (!class_exists($this->class)) {
            throw new \Exception('Could not find class "%s"', $this->class);
        }

        if (!class_exists(\ReflectionClass::class)) {
            throw new \Exception('PHP Reflector is missing');
        }

        $this->reflectionClass = new \ReflectionClass($this->class);
        $this->calledMethod = $this->getCalledMethod();

        $parameters = [];

        if (null !== $this->calledMethod) {
            if (!$this->calledMethod->isPublic()) {
                throw new \Exception('Could not resolve not public method');
            }

            foreach ($this->calledMethod->getParameters() as $parameter) {
                $parameterClass = $parameter->getClass();

                if (null === $parameterClass) {
                    if (isset($this->container[$parameter->getName()])) {
                        $parameters[$parameter->getName()] = $this->container[$parameter->getName()];
                    } else {
                        if (!$parameter->isDefaultValueAvailable()) {
                            throw new \Exception('Autowire of parameters failed');
                        }
                    }
                } else {
                    $parameterClassName = $parameterClass->getName();

                    if ($this->options['resolveInjectedClasses']) {
                        $parameters[$parameter->getName()] = (new self($parameterClass->getName(), [], $this->options['useOptionsForInjectedClasses'] ? $this->options : []))->resolve()->getResolved();
                    } else {
                        $parameters[$parameter->getName()] = new $parameterClassName;
                    }
                }
            }
        }

        if ($this->options['calledMethod'] === '__construct') {
            $this->resolved = $this->reflectionClass->newInstanceArgs($parameters);
        } else {
            $object = new $this->class;
            $this->resolved = call_user_func_array(array($object, $this->options['calledMethod']), $parameters);
        }

        return $this;
    }

    private function getCalledMethod()
    {
        for($i = 0; $i < count($this->reflectionClass->getMethods()); $i++) {
            if ($this->reflectionClass->getMethods()[$i]->getName() === $this->options['calledMethod']) {
                return $this->reflectionClass->getMethods()[$i];
            }
        }

        return null;
    }

    public function getResolved()
    {
        return $this->resolved;
    }

    private function resolveOptions(array $options): array
    {
        return array_merge([
            'calledMethod' => '__construct',
            'resolveInjectedClasses' => false,
            'useOptionsForInjectedClasses' => false,
        ], $options);
    }
}

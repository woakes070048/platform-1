<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The default strategy for grid names.
 */
class NameStrategy implements NameStrategyInterface
{
    const DELIMITER = ':';

    /** @var RequestStack */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    #[\Override]
    public function getDelimiter()
    {
        return self::DELIMITER;
    }

    #[\Override]
    public function parseGridName($fullName)
    {
        $parts = $this->parseGridNameAndScope($fullName);
        return $parts[0];
    }

    #[\Override]
    public function parseGridScope($fullName)
    {
        $parts = $this->parseGridNameAndScope($fullName);
        return $parts[1];
    }

    #[\Override]
    public function buildGridFullName($name, $scope)
    {
        $result = $name;

        if ($scope) {
            $result .= $this->getDelimiter() . $scope;
        }

        $this->parseGridNameAndScope($result); // validate result

        return $result;
    }

    #[\Override]
    public function getGridUniqueName($name)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return $name;
        }

        $uniqueName = $name;
        $widgetId = $request->get('_widgetId');
        if ($widgetId) {
            $uniqueName = sprintf('%s_w%s', $uniqueName, $widgetId);
        } elseif ($request->query->count() === 1) {
            $paramName = array_keys($request->query->all())[0];
            if (str_starts_with($paramName, $name)) {
                $uniqueName = $paramName;
            }
        }

        return $uniqueName;
    }

    /**
     * @param string $name
     * @return array
     * @throws InvalidArgumentException
     */
    private function parseGridNameAndScope($name)
    {
        if (substr_count($name, self::DELIMITER) > 1) {
            throw new InvalidArgumentException(
                sprintf(
                    'Grid name "%s" is invalid, it should not contain more than one occurrence of "%s".',
                    $name,
                    self::DELIMITER
                )
            );
        }
        $result = array_pad(explode(self::DELIMITER, $name, 2), 2, '');

        if (!$result[0]) {
            throw new InvalidArgumentException(
                sprintf(
                    'Grid name "%s" is invalid, name must be not empty.',
                    $name
                )
            );
        }

        return $result;
    }
}

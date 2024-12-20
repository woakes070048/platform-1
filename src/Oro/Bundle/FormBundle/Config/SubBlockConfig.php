<?php

namespace Oro\Bundle\FormBundle\Config;

/**
 * Sub block configuration class. This class stores data from system configuration files,
 * later this data used to build form of configuration on UI
 */
class SubBlockConfig implements FormConfigInterface
{
    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $descriptionStyle;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var int
     */
    protected $priority;

    /**
     * @var string
     */
    protected $tooltip;

    /**
     * @var array
     */
    protected $data = array();

    /**
     * @var bool
     */
    protected $useSpan;

    public function __construct($code)
    {
        $this->code = $code;
    }

    /**
     * @param $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function getDescriptionStyle(): ?string
    {
        return $this->descriptionStyle;
    }

    /**
     * @param string|null $descriptionStyle
     *
     * @return $this
     */
    public function setDescriptionStyle(?string $descriptionStyle): self
    {
        $this->descriptionStyle = $descriptionStyle;

        return $this;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param int $priority
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param $data
     * @return $this
     */
    public function addData($data)
    {
        $this->data[] = $data;

        return $this;
    }

    /**
     * @param boolean $useSpan
     * @return $this
     */
    public function setUseSpan($useSpan)
    {
        $this->useSpan = $useSpan;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getUseSpan()
    {
        return $this->useSpan;
    }

    /**
     * @return string
     */
    public function getTooltip()
    {
        return $this->tooltip;
    }

    /**
     * @param string $tooltip
     */
    public function setTooltip($tooltip)
    {
        $this->tooltip = $tooltip;
    }

    /**
     * @return array
     */
    #[\Override]
    public function toArray()
    {
        return [
            'code'        => $this->code,
            'title'       => $this->title,
            'description' => $this->description,
            'descriptionStyle' => $this->descriptionStyle,
            'tooltip'     => $this->tooltip,
            'data'        => $this->data,
            'useSpan'     => $this->useSpan !== null ? $this->useSpan : true
        ];
    }
}

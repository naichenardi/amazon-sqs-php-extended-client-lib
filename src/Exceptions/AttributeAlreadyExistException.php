<?php


namespace AwsExtended\Exceptions;

class AttributeAlreadyExistException extends \RuntimeException
{
    public function __construct(string $attribute)
    {
        parent::__construct("Attribute '$attribute' already exists!");
    }

}
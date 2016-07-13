<?php

/**
 * This file is part of the Version package.
 *
 * Copyright (c) Nikola Posa <posa.nikola@gmail.com>
 *
 * For full copyright and license information, please refer to the LICENSE file,
 * located at the package root folder.
 */

namespace Version\Tests\Constraint;

use PHPUnit_Framework_TestCase;
use Version\Constraint\Parser\StandardParser;
use Version\Constraint\Constraint;
use Version\Constraint\CompositeConstraint;
use Version\Exception\InvalidConstraintStringException;

/**
 * @author Nikola Posa <posa.nikola@gmail.com>
 */
class ParserTest extends PHPUnit_Framework_TestCase
{
    protected $parser;

    protected function setUp()
    {
        $this->parser = new StandardParser();
    }

    public static function assertConstraint($operator, $operandString, Constraint $constraint)
    {
        self::assertEquals($operator, $constraint->getOperator());
        self::assertEquals($operandString, (string) $constraint->getOperand());
    }

    public static function assertCompositeConstraint($type, array $constraints, CompositeConstraint $constraint)
    {
        self::assertEquals($type, $constraint->getType());
        $actualConstraints = $constraint->getConstraints();
        self::assertCount(count($constraints), $actualConstraints);
    }

    public function testParsingSimpleConstraint()
    {
        $constraint = $this->parser->parse('>=1.2.0');

        $this->assertInstanceOf(Constraint::class, $constraint);
        $this->assertConstraint('>=', '1.2.0', $constraint);
    }

    public function testParsingRangeConstraint()
    {
        $this->markTestIncomplete();

        $constraint = $this->parser->parse('>=1.2.3 <1.3.0');

        $this->assertInstanceOf(CompositeConstraint::class, $constraint);
        $this->assertCompositeConstraint(
            CompositeConstraint::TYPE_AND,
            [
                ['operator' => '>=', 'operand' => '1.2.3'],
                ['operator' => '<', 'operand' => '1.3.0'],
            ],
            $constraint
        );
    }

    public function testParsingConstraintWithLogicalOperators()
    {
        $this->markTestIncomplete();

        $constraint = $this->parser->parse('>=1.0 <1.1 || >=1.2');

        $this->assertInstanceOf(CompositeConstraint::class, $constraint);
    }

    public function testExceptionIsRaisedIfConstraintStringIsNotString()
    {
        $this->setExpectedException(
            InvalidConstraintStringException::class,
            'Constraint string should be of type string; integer given'
        );

        $this->parser->parse(123);
    }

    public function testExceptionIsRaisedIfConstraintStringIsEmpty()
    {
        $this->setExpectedException(
            InvalidConstraintStringException::class,
            'Constraint string must not be empty'
        );

        $this->parser->parse('  ');
    }

    public function testExceptionIsRaisedIfConstraintStringCannotBeParsed()
    {
        $this->setExpectedException(InvalidConstraintStringException::class);

        $this->parser->parse('invalid');
    }
}

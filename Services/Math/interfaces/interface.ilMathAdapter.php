<?php
/******************************************************************************
 *
 * This file is part of ILIAS, a powerful learning management system.
 *
 * ILIAS is licensed with the GPL-3.0, you should have received a copy
 * of said license along with the source code.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 *      https://www.ilias.de
 *      https://github.com/ILIAS-eLearning
 *
 *****************************************************************************/
/**
 * Interface ilMathAdapter
 * @author Michael Jansen <mjansen@databay.de>
 */
interface ilMathAdapter
{
    /**
     * Adds two numbers
     * @param  mixed $left_operand
     * @param  mixed $right_operand
     * @param int $scale
     * @return mixed
     */
    public function add($left_operand, $right_operand, $scale = null);

    /**
     * Subtracts two numbers
     * @param  mixed $left_operand
     * @param  mixed $right_operand
     * @param int $scale
     * @return mixed
     */
    public function sub($left_operand, $right_operand, $scale = null);

    /**
     * Multiplies two numbers
     * @param  mixed $left_operand
     * @param  mixed $right_operand
     * @param int $scale
     * @return mixed
     */
    public function mul($left_operand, $right_operand, $scale = null);

    /**
     * Divides two numbers
     * @param  mixed $left_operand
     * @param  mixed $right_operand
     * @param int $scale
     * @return mixed
     * @throws ilMathDivisionByZeroException
     */
    public function div($left_operand, $right_operand, $scale = null);

    /**
     * Gets modulus of two numbers
     * @param  mixed $left_operand
     * @param  mixed $right_operand
     * @return mixed
     * @throws ilMathDivisionByZeroException
     */
    public function mod($left_operand, $right_operand);

    /**
     * Raises a number to another
     * @param  mixed $left_operand
     * @param  mixed $right_operand
     * @param int $scale
     * @return mixed
     */
    public function pow($left_operand, $right_operand, $scale = null);

    /**
     * Gets the square root of a number
     * @param  mixed $operand
     * @param int $scale
     * @return mixed
     */
    public function sqrt($operand, $scale = null);


    /**
     * Compares two numbers
     * @param  mixed $left_operand
     * @param  mixed $right_operand
     * @param int $scale
     * @return mixed
     */
    public function comp($left_operand, $right_operand, $scale = null);

    /**
     * Checks whether or not two numbers are identical
     * @param  mixed $left_operand
     * @param  mixed $right_operand
     * @param int $scale
     * @return bool
     */
    public function equals($left_operand, $right_operand, $scale = null);

    /**
     * @param  mixed $left_operand
     * @param  int $scale
     * @return mixed
     */
    public function applyScale($left_operand, int $scale = null);

    /**
     * @param mixed $value
     * @return string
     */
    public function round($value, int $precision = 0);
}

<?php
/**
 * This file is an abstract of the Exception handler
 * in the event that a connection is invalid it will
 * be thrown within out application.
 *
 * @author      Matthew Baggett
 * @email       matthew@baggett.me
 * @copyright   GPL license
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @link        https://github.com/matthewbaggett/MySQL2PDO
 */

/**
 * MySQL2PDOException
 *
 * Throw exception handler. Place custom Exception logic here
 *
 * @author      Matthew Baggett
 * @email       matthew@baggett.me
 * @copyright   GPL license
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @link        https://github.com/matthewbaggett/MySQL2PDO
 * @extends     Exception
 */
class MySQL2PDOException extends Exception {}
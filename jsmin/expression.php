<?php
/**
 *
 */
abstract class Expression extends Node {
	/**
	 * Used with all expressions
	 */
	protected $left;
	/**
	 * Only used with ? :
	 */
	protected $middle;
	/**
	 * Only unused with unary ops
	 */
	protected $right;

	public function __construct() {
		parent::__construct();
	}

	abstract public function precedence();

	public function first() {
		if ($this->left) {
			return $this->left->first();
		} else {
			return $this;
		}
	}

	public function left() {
		return $this->left;
	}

	public function last() {
		return $this;
	}

	public function mid() {
		return $this->middle;
	}

	public function right() {
		return $this->right;
	}

	public function value() {
		return null;
	}

	public function asString() {
		return null;
	}

	public function asNumber() {
		return null;
	}

	public function asBoolean() {
		return null;
	}

	public function type() {
		return null;
	}

	public function isLocal() {
		return false;
	}

	public function actualType() {
		return $this->type();
	}

	public function negate() {
		return new NotExpression($this);
	}

	public function looseBoolean() {
		return $this;
	}

	public function boolean() {
		if ($this->type() == 'boolean') {
			return $this;
		}

		return new NotExpression(new NotExpression($this));
	}

	public function represents() {
		return $this;
	}

	public function property($key) {
		return null;
	}

	public function mayInline() {
		return false;
	}

	public function unassign() {
		$left = $this;
		while ($left) {
			if ($left instanceof IdentifierExpression) {
				$left->reassigned(false);
				break;
			}

			if ($left === ($left = $left->left())) {
				break;
			}
		}
	}

	protected function group(Expression $base, Expression $hook, $left = true) {
		$inner = $hook->toString();

		if ($hook instanceof ConstantExpression || !$r = $hook->precedence()) {
			return $inner;
		}

		$l = $base->precedence();

		if (!$left) {
			++$l;
		}

		if ($l > $r) {
			return '(' . $inner . ')';
		} else {
			return $inner;
		}
	}

	public function isRedundant() {
		return true;
	}

	public function hasStructure(Node $cmp) {
		return $cmp instanceof Expression;
	}

	public function removeUseless() {
		return $this;
	}

	public function isInfinity() {
		return false;
	}

	public function isImmutable() {
		return false;
	}

	public function binary($op) {
		$space = AST::$options['beautify'] ? ' ' : '';
		return $this->group($this, $this->left) . $space . $op . $space . $this->group($this, $this->right, false);
	}

	public function unary($op) {
		$left = $this->group($this, $this->left);
		return $op
			. (($op === '+' && $left[0] === '+' && $left[1] !== '+') || ($op === '-' && $left[0] === '-' && $left[1] !== '-') ? ' ' : '')
			. $left;
	}
}

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

		if ($this->left instanceof Node) {
			$this->left->parent($this);
		}

		if ($this->middle instanceof Node) {
			$this->middle->parent($this);
		}

		if ($this->right instanceof Node) {
			$this->right->parent($this);
		} elseif (is_array($this->right)) {
			foreach($this->right as $p) {
				$p->parent($this);
			}
		}
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

	public function represents() {
		return $this;
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
		$l = $base->precedence();
		$r = $hook->precedence();

		if (!$r) {
			return (string)$hook;
		}

		if (($left && $l > $r) || (!$left && $l >= $r)) {
			return '(' . $hook . ')';
		} else {
			return (string)$hook;
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

	public function binary($op) {
		$space = AST::$options['beautify'] ? ' ' : '';
		return $this->group($this, $this->left) . $space . $op . $space . $this->group($this, $this->right, false);
	}

	public function unary($op) {
		return $op . $this->group($this, $this->left, false);
	}
}

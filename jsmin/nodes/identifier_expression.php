<?php
class IdentifierExpression extends ConstantExpression {
	protected $write = false;

	public function __construct(Identifier $i, $write = false) {
		parent::__construct($i);
		$this->write = $write;
	}

	public function toString() {
		return $this->left->toString();
	}

	public function visit(AST $ast, Node $parent = null) {
		// check for some common variables
		if (!$this->write && !$this->left->declared()) {
			switch ($this->left->toString()) {
			case 'undefined':
				return new VoidExpression(new Number(0));
			case 'NaN':
				return new DivExpression(new Number(0), new Number(0));
			}
		}

		if (!$this->write && !AST::$options['no-inlining']) {
			if ($ast->hasStats() && !$this->left->reassigned() && ($init = $this->left->initializer()) && $init->mayInline()) {
				/*
				 * We'll have to verify that inlining will cost less than keeping the variable
				 *
				 * Do:
				 * var a=5;alert(a);
				 * alert(5);
				 *
				 * Do not do:
				 * var a=2500;alert(a,a,a,a,a);
				 * alert(2500,2500,2500,2500,2500);
				 */

				$valueLength = strlen($init->toString());
				$usage = $this->left->used();

				if ((($usage - 1) * $valueLength) < (($usage * 1) + 4 + $valueLength)) {
					$this->left->used(false);
					AST::warn('Replacing reference to ' . $this->left->toString() . ' with its constant value `' . $init->toString() . '`');
					return $init;
				}
			}
		}

		return $this;
	}

	public function asString() {
		if (!$this->reassigned() && ($init = $this->left->initializer()) && $init->mayInline()) {
			return $init->asString();
		}
	}

	public function collectStatistics(AST $ast, $reassigned = false) {
		$this->left->used(true);

		if ($reassigned) {
			$this->reassigned(true);
		}
	}

	public function declared() {
		return $this->left->declared();
	}

	public function reassigned($bool = null) {
		return $this->left->reassigned($bool);
	}

	public function initializer(Expression $e = null) {
		return $this->left->initializer($e);
	}

	public function isLocal() {
		return $this->declared();
	}

	public function keep($min = 0) {
		return $this->left->keep($min);
	}

	public function write() {
		$this->write = true;
	}

	public function used($bool = null) {
		return max($this->left->used($bool), $this->write ? 1 : 0);
	}

	public function gone() {
		$this->used(false);
		$this->reassigned(false);
	}

	public function value() {
		return $this->left->name();
	}

	public function mayInline() {
		return $this->left->isLocal() && !$this->initializer() && !$this->reassigned();
	}

	public function __toString() {
		return $this->toString();
	}

	public function countLetters(&$letters) {
		if (!$this->left->isLocal()) {
			$this->counter((string)$this->left->name(), $letters);
		}
	}

	public function get() {
		return $this->left;
	}
}

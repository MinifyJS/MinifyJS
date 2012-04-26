<?php
class IdentifierExpression extends ConstantExpression {
	protected $write = false;

	public function __construct(Identifier $i, $write = false) {
		parent::__construct($i);
		$this->write = $write;
	}

	public function visit(AST $ast) {
		if (!$this->write && (!$this->left->declared() || !AST::$options['mangle'])) {
			switch ($this->left->toString()) {
			case 'undefined':
				return new VoidExpression(new Number(0));
				break;
			}
		}

		if (!$this->write) {
			// check for some common variables
			if ($ast->hasStats() && $init = $this->left->initializer()) {
				$this->left->used(false);
				return $init;
			}
		}

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->used(true);
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

	public function value() {
		return $this->left->name();
	}

	public function __toString() {
		return $this->left->toString();
	}

	public function get() {
		return $this->left;
	}
}

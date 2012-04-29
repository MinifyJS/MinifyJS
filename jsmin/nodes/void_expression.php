<?php
class VoidExpression extends Expression {
	public function __construct(Expression $l) {
		$this->left = $l;
		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->left = $this->left->visit($ast);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
	}

	public function isEmpty() {
		return $this->left->isConstant();
	}

	public function asBoolean() {
		if ($this->isConstant()) {
			return false;
		}
	}

	public function mayInline() {
		return $this->isConstant();
	}

	public function toString() {
		return 'void' . Stream::legalStart($this->group($this, $this->left, false));
	}

	public function isConstant() {
		return $this->left->isConstant();
	}

	public function gone() {
		$this->left->gone();
	}

	public function type() {
		return 'undefined';
	}

	public function precedence() {
		return 14;
	}

	public function removeUseless() {
		return $this->left->removeUseless();
	}
}
<?php
class BitwiseNotExpression extends Expression {
	public function __construct(Expression $left) {
		$this->left = $left;
		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->left = $this->left->visit($ast);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
	}

	public function toString() {
		return '~' . $this->group($this, $this->left, false);
	}

	public function isConstant() {
		return $this->left->isConstant();
	}

	public function type() {
		return 'number';
	}

	public function precedence() {
		return 14;
	}
}
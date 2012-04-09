<?php
class DeleteExpression extends Expression {
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

	public function value() {
		return null;
	}

	public function toString() {
		return 'delete' . Stream::legalStart($this->group($this, $this->left, false));
	}

	public function isConstant() {
		return false;
	}

	public function type() {
		return null;
	}

	public function precedence() {
		return 14;
	}
}

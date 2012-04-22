<?php
class InExpression extends Expression {
	public function __construct(Expression $left, Expression $right) {
		$this->left = $left;
		$this->right = $right;

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->right = $this->right->visit($ast);
		$this->left = $this->left->visit($ast);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
		$this->right->collectStatistics($ast);
	}

	public function toString() {
		return Stream::legalEnd($this->group($this, $this->left))
			. 'in'
			. Stream::legalStart($this->group($this, $this->right, false));
	}

	public function type() {
		return 'boolean';
	}

	public function precedence() {
		return 10;
	}
}
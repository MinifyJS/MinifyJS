<?php
class DotExpression extends Expression {
	public function __construct(Expression $left, Identifier $right) {
		$this->left = $left;
		$this->right = $right;
		parent::__construct();
	}

	public function visit(AST $ast) {
		//if ($this->left instanceof IdentifierExpression) {
			$this->left = $this->left->visit($ast);
		//}

		return $this;
	}

	public function collectStatistics(AST $ast) {
		if ($this->left instanceof IdentifierExpression) {
			$this->left->collectStatistics($ast);
		}
	}

	public function toString() {
		return $this->group($this, $this->left) . '.' . $this->right->toString();
	}

	public function isRedundant() {
		return $this->left->isRedundant();
	}

	public function precedence() {
		return 17;
	}
}

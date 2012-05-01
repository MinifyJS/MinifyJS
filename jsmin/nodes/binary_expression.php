<?php
abstract class BinaryExpression extends Expression {
	public function __construct($type, Expression $left, Expression $right) {
		$this->type = $type;
		$this->left = $left;
		$this->right = $right;

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->left = $this->left->visit($ast);
		$this->right = $this->right->visit($ast);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
		$this->right->collectStatistics($ast);
	}

	public function toString() {
		return $this->binary($this->type);
	}

	public function type() {
		return null;
	}

	public function gone() {
		$this->left->gone();
		$this->right->gone();
	}

	public function precedence() {
		return 9;
	}
}

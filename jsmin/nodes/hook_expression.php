<?php
class HookExpression extends Expression {
	public function __construct(Expression $hook, Expression $then, Expression $else) {
		$this->left = $hook;
		$this->middle = $then;
		$this->right = $else;

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->left = $this->left->visit($ast);
		$this->middle = $this->middle->visit($ast);
		$this->right = $this->right->visit($ast);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
		$this->middle->collectStatistics($ast);
		$this->right->collectStatistics($ast);
	}

	public function toString() {
		return $this->group($this, $this->left)
			. '?' . $this->group($this, $this->middle, false)
			. ':' . $this->group($this, $this->right);
	}

	public function type() {
		return null;
	}

	public function precedence() {
		return 2;
	}
}

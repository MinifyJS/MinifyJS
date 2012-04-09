<?php
class CallExpression extends Expression {
	public function __construct(Expression $what, array $args) {
		$this->left = $what;
		$this->right = $args;
		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->left = $this->left->visit($ast);

		foreach($this->right as $i => $r) {
			$this->right[$i] = $r->visit($ast);
		}

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
		foreach($this->right as $r) {
			$r->collectStatistics($ast);
		}
	}

	public function toString() {
		return $this->group($this, $this->left) . '(' . implode(',', $this->right) . ')';
	}

	public function precedence() {
		return 17;
	}

	public function isRedundant() {
		return false;
	}
}

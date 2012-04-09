<?php
class NewExpression extends Expression {
	public function __construct(Expression $a, array $b) {
		$this->left = $a;
		$this->right = $b;

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->left = $this->left->visit($ast);
		foreach($this->right as $i => $n) {
			$this->right[$i] = $n->visit($ast);
		}

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
		foreach($this->right as $n) {
			$n->collectStatistics($ast);
		}
	}

	public function toString() {
		return 'new' . Stream::legalStart($this->group($this, $this->left)) . '(' . implode(',', $this->right) . ')';
	}

	public function precedence() {
		return 16;
	}
}
<?php
class IndexExpression extends Expression {
	public function __construct(Expression $left, Expression $right) {
		$this->left = $left;
		$this->right = $right;

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->left = $this->left->visit($ast);
		$this->right = $this->right->visit($ast);

		if ($this->right()->type() === 'string') {
			$test = $this->right->asString();
			if (Identifier::isValid($test)) {
				return new DotExpression($this->left, new Identifier(null, $test));
			}
		}

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
		$this->right->collectStatistics($ast);
	}

	public function toString() {
		return $this->group($this, $this->left) . '[' . $this->right . ']';
	}

	public function isRedundant() {
		return $this->left->isRedundant() && $this->right->isRedundant();
	}

	public function precedence() {
		return 17;
	}
}
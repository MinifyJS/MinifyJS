<?php
class IndexExpression extends Expression {
	public function __construct(Expression $left, Expression $right) {
		$this->left = $left;
		$this->right = $right;

		parent::__construct();
	}

	public function visit(AST $ast, Node $parent = null) {
		$this->left = $this->left->visit($ast, $this);
		$this->right = $this->right->visit($ast, $this);

		if (null !== $test = $this->right->asString()) {
			if (Identifier::isValid($test)) {
				$result = new DotExpression($this->left, new Identifier(null, $test));
				return $result->visit($ast);
			}
		}

		return $this;
	}

	public function gone() {
		$this->left->gone();
		$this->right->gone();
	}

	public function collectStatistics(AST $ast, $write = false) {
		$this->left->collectStatistics($ast, $write);
		$this->right->collectStatistics($ast);
	}

	public function isLocal() {
		return $this->left->isLocal();
	}

	public function toString() {
		return $this->group($this, $this->left) . '[' . $this->right . ']';
	}

	public function isRedundant() {
		return $this->left->isRedundant() && $this->right->isRedundant();
	}

	public function hasSideEffects() {
		return $this->left->hasSideEffects() || $this->right->hasSideEffects();
	}

	public function countLetters(&$letters) {
		$this->left->countLetters($letters);
		$this->right->countLetters($letters);
	}

	public function precedence() {
		return 17;
	}
}
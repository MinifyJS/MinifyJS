<?php
class UnaryExpression extends Expression {
	public function __construct(Expression $left) {
		$this->left = $left;

		parent::__construct();
	}

	public function visit(AST $ast, Node $parent = null) {
		$this->left = $this->left->visit($ast, $this);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
	}

	public function gone() {
		$this->left->gone();
	}

	public function isConstant() {
		return $this->left->isConstant();
	}

	public function precedence() {
		return 14;
	}

	public function resolveLeftSequence() {
		if ($this->left instanceof CommaExpression) {
			$x = $this->left->nodes();

			return AST::bestOption(array(
				new CommaExpression(array(
					new CommaExpression(array_slice($x, 0, -1)),
					new static(end($x))
				)),
				$this
			));
		}

		return $this;
	}

	public function hasSideEffects() {
		return $this->left->hasSideEffects();
	}

	public function countLetters(&$letters) {
		$this->left->countLetters($letters);
	}

	public function removeUseless() {
		return $this->left->removeUseless();
	}
}
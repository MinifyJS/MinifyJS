<?php
class UnaryExpression extends Expression {
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

	public function gone() {
		$this->left->gone();
	}

	public function isConstant() {
		return $this->left->isConstant();
	}

	public function precedence() {
		return 14;
	}

	public function countLetters(&$letters) {
		$this->left->countLetters($letters);
	}

	public function removeUseless() {
		return $this->left->removeUseless();
	}
}
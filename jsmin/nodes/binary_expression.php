<?php
abstract class BinaryExpression extends Expression {
	public function __construct($type, Expression $left, Expression $right) {
		$this->type = $type;
		$this->left = $left;
		$this->right = $right;

		parent::__construct();
	}

	public function visit(AST $ast, Node $parent = null) {
		$that = clone $this;
		$that->left = $that->left->visit($ast, $this);
		$that->right = $that->right->visit($ast, $this);

		return $that;
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

	public function countLetters(&$letters) {
		$this->left->countLetters($letters);
		$this->right->countLetters($letters);
	}

	public function hasSideEffects() {
		return $this->left->hasSideEffects() || $this->right->hasSideEffects();
	}

	public function precedence() {
		return 9;
	}
}

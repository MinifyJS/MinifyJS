<?php
class InstanceofExpression extends Expression {
	public function __construct(Expression $left, Expression $right) {
		$this->left = $left;
		$this->right = $right;

		parent::__construct();
	}

	public function visit(AST $ast, Node $parent = null) {
		$result = new InstanceofExpression($this->left->visit($ast, $this), $this->right->visit($ast, $this));
		return $result->resolveLeftSequence();
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
		$this->right->collectStatistics($ast);
	}

	public function gone() {
		$this->left->gone();
		$this->right->gone();
	}

	public function toString() {
		$left = $this->group($this, $this->left);
		$right = $this->group($this, $this->right, false);

		if (AST::$options['beautify']) {
			return $left . ' instanceof ' . $right;
		}

		return Stream::legalEnd($left) . 'instanceof' . Stream::legalStart($right);
	}

	public function type() {
		return 'boolean';
	}

	public function countLetters(&$letters) {
		foreach(array('i', 'n', 's', 't', 'a', 'n', 'c', 'e', 'o', 'f') as $l) {
			$letters[$l] += 1;
		}

		$this->left->countLetters($letters);
		$this->right->countLetters($letters);
	}

	public function hasSideEffects() {
		return $this->left->hasSideEffects() || $this->right->hasSideEffects();
	}

	public function precedence() {
		return 10;
	}
}

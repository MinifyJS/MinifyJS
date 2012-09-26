<?php
class InExpression extends Expression {
	public function __construct(Expression $left, Expression $right) {
		$this->left = $left;
		$this->right = $right;

		parent::__construct();
	}

	public function visit(AST $ast, Node $parent = null) {
		$result = new InExpression($this->left->visit($ast, $this), $this->right->visit($ast, $this));
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

	public function toString($noIn = false) {
		$left = $this->group($this, $this->left);
		$right = $this->group($this, $this->right, false);

		if (AST::$options['beautify']) {
			$output = $left . ' in ' . $right;
		} else {
			$output = Stream::legalEnd($left) . 'in' . Stream::legalStart($right);
		}

		if ($noIn) {
			$output = '(' . $output . ')';
		}

		return $output;
	}

	public function countLetters(&$letters) {
		$letters['i'] += 1;
		$letters['n'] += 1;

		$this->left->countLetters($letters);
		$this->right->countLetters($letters);
	}

	public function type() {
		return 'boolean';
	}

	public function hasSideEffects() {
		return $this->left->hasSideEffects() || $this->right->hasSideEffects();
	}

	public function precedence() {
		return 10;
	}
}

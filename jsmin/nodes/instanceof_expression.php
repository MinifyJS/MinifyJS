<?php
class InstanceofExpression extends Expression {
	public function __construct(Expression $left, Expression $right) {
		$this->left = $left;
		$this->right = $right;

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->right = $this->right->visit($ast);
		$this->left = $this->left->visit($ast);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
		$this->right->collectStatistics($ast);
	}

	public function toString() {
		$left = $this->group($this, $this->left);
		$right = $this->group($this, $this->right, false);

		if (AST::$options['beautify']) {
			return $left . ' instanceof ' . $right;
		}

		return Stream::legalEnd($this->group($this, $this->left))
			. 'instanceof'
			. Stream::legalStart($this->group($this, $this->right, false));
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

	public function precedence() {
		return 10;
	}
}
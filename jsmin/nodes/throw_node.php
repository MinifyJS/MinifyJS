<?php
class ThrowNode extends Node {
	protected $exception;

	public function __construct(Expression $exception) {
		$this->exception = $exception;

		parent::__construct();
	}

	public function visit(AST $ast, Node $parent = null) {
		$this->exception = $this->exception->visit($ast, $this);

		return $this;
	}

	public function gone() {
		$this->exception->gone();
	}

	public function collectStatistics(AST $ast) {
		$this->exception->collectStatistics($ast);
	}

	public function toString() {
		if (AST::$options['beautify']) {
			return 'throw ' . $this->exception->toString();
		}

		return 'throw' . Stream::legalStart($this->exception->toString());
	}

	public function countLetters(&$letters) {
		foreach(array('t', 'h', 'r', 'o', 'w') as $l) {
			$letters[$l] += 1;
		}

		$this->exception->countLetters($letters);
	}

	public function value() {
		return $this->exception;
	}

	public function moveExpression(Expression $x) {
		$this->value = new CommaExpression(array_merge($x->nodes(), $this->exception->nodes()));
		return true;
	}

	public function isBreaking() {
		return true;
	}
}
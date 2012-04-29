<?php
class ThrowNode extends Node {
	protected $exception;

	public function __construct(Expression $exception) {
		$this->exception = $exception;

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->exception = $this->exception->visit($ast);

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

	public function value() {
		return $this->exception;
	}

	public function isBreaking() {
		return true;
	}
}
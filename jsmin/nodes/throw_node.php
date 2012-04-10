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

	public function collectStatistics(AST $ast) {
		$this->exception->collectStatistics($ast);
	}

	public function toString() {
		return 'throw' . Stream::legalStart($this->exception->toString());
	}
}
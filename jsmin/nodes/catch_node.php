<?php
class CatchNode extends Node {
	protected $variable;
	protected $body;

	public function __construct(Identifier $var, Node $body) {
		$this->variable = $var;
		$this->body = $body;
	}

	public function visit(AST $ast) {
		$this->body = $this->body->visit($ast);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->variable->used(true);

		$this->body->collectStatistics($ast);
	}

	public function toString() {
		return 'catch(' . $this->variable->toString() . '){'
			. Stream::trimSemicolon($this->body->asBlock()->toString(true))
		. '}';
	}
}
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

	public function gone() {
		$this->variable->used(false);
		$this->variable->reassigned(false);
		$this->body->gone();
	}

	public function collectStatistics(AST $ast) {
		$this->variable->used(true);
		// not actually reassigned, but don't take the risk
		$this->variable->reassigned(true);

		$this->body->collectStatistics($ast);
	}

	public function toString() {
		return 'catch(' . $this->variable->toString() . '){'
			. Stream::trimSemicolon($this->body->asBlock()->toString(true))
		. '}';
	}
}
<?php
class WithNode extends Node {
	protected $object;
	protected $body;

	public function __construct(Expression $object, Node $body) {
		$this->object = $object;
		$this->body = $body;
	}

	public function visit(AST $ast) {
		$this->object = $this->object->visit($ast);
		$this->body = $this->body->visit($ast);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$ast->visitScope()->usesWith();

		$this->object->collectStatistics($ast);
		$this->body->collectStatistics($ast);
	}

	public function toString() {
		return 'with(' . $this->object->toString() . ')' . $this->body->asBlock()->toString();
	}
}
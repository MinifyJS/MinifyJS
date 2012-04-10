<?php
class ForInNode extends Node {
	protected $iterator;
	protected $object;
	protected $body;

	public function __construct(Node $iterator, Node $object, Node $body) {
		$this->iterator = $iterator;
		$this->object = $object;
		$this->body = $body;

		$this->body->parent($this);

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->iterator = $this->iterator->visit($ast);
		$this->object = $this->object->visit($ast);
		$this->body = $this->body->visit($ast);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->iterator->collectStatistics($ast);
		$this->object->collectStatistics($ast);
		$this->body->collectStatistics($ast);
	}

	public function last() {
		return $this->body->last();
	}

	public function toString() {
		return 'for(' . Stream::legalEnd($this->iterator->toString()) . 'in' . Stream::legalStart($this->object->toString()) . ')' . $this->body->asBlock()->toString(null, true);
	}
}

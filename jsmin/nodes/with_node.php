<?php
class WithNode extends Node {
	protected $object;
	protected $body;

	public function __construct(Expression $object, Node $body) {
		$this->object = $object;
		$this->body = $body;
	}

	public function visit(AST $ast, Node $parent = null) {
		$this->object = $this->object->visit($ast, $this);
		$this->body = $this->body->visit($ast, $this);

		if ($this->body->isRedundant()) {
			if ($ast->hasStats()) {
				$ast->visitScope()->usesWith(-1);
			}

			return new CommaExpression(array($this->object, new VoidExpression(new Number(0))));
		}

		return $this;
	}

	public function gone() {
		$this->object->gone();
		$this->body->gone();
	}

	public function collectStatistics(AST $ast) {
		$ast->visitScope()->usesWith();

		$this->object->collectStatistics($ast);
		$this->body->collectStatistics($ast);
	}

	public function countLetters(&$letters) {
		$letters['w'] += 1;
		$letters['i'] += 1;
		$letters['t'] += 1;
		$letters['h'] += 1;

		$this->object->countLetters($letters);
		$this->body->countLetters($letters);
	}

	public function moveExpression(Expression $x) {
		$this->object = new CommaExpression(array_merge($x->nodes(), $this->object->nodes()));
		return true;
	}


	public function toString() {
		return 'with(' . $this->object->toString() . ')' . $this->body->asBlock()->toString(null, true);
	}
}

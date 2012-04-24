<?php
class DebuggerNode extends Node {
	public function visit(AST $ast) {
		return $this;
	}

	public function collectStatistics(AST $ast) {}

	public function toString() {
		return 'debugger';
	}
}

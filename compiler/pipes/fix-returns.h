#pragma once

#include "compiler/function-pass.h"

class FixReturnsPass : public FunctionPassBase {
public:
  VertexPtr on_enter_vertex(VertexPtr root);

  bool user_recursion(VertexPtr root) {
    return root->type() == op_fork;
  }
};

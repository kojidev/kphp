#include "compiler/pipes/check-instance-props.h"

#include "compiler/data/class-data.h"
#include "compiler/data/var-data.h"
#include "compiler/name-gen.h"
#include "compiler/phpdoc.h"

VertexPtr CheckInstancePropsPass::on_enter_vertex(VertexPtr v, LocalT *) {

  if (v->type() == op_instance_prop) {
    ClassPtr klass = resolve_class_of_arrow_access(current_function, v);
    if (klass) {   // если null, то ошибка доступа к непонятному свойству уже кинулась в resolve_class_of_arrow_access()
      auto m = klass->members.get_instance_field(v->get_string());

      if (m) {
        v->set_var_id(m->var);
        init_class_instance_var(v, m, klass);
      } else {
        kphp_error(0, format("Invalid property access ...->%s: does not exist in class %s", v->get_c_string(), klass->name.c_str()));
      }
    }
  }

  return v;
}

/**
 * Если при объявлении поля класса написано / ** @var int|false * / к примеру, делаем type_rule из phpdoc.
 * Это заставит type inferring принимать это во внимание, и если где-то выведется по-другому, будет ошибка.
 * С инстансами это тоже работает, т.е. / ** @var \AnotherClass * / будет тоже проверяться при выводе типов.
 */
void CheckInstancePropsPass::init_class_instance_var(VertexPtr v, const ClassMemberInstanceField *field, ClassPtr klass) {
  kphp_assert(field->var->class_id == klass);

  // сейчас phpdoc у class var'а парсится каждый раз при ->обращении;
  // это уйдёт, когда вместо phpdoc_token будем хранить что-то более умное (что парсится 1 раз и хранит type_rule)
  if (field->phpdoc_token) {
    std::string var_name, type_str;
    if (PhpDocTypeRuleParser::find_tag_in_phpdoc(field->phpdoc_token->str_val, php_doc_tag::var, var_name, type_str)) {
      VertexPtr doc_type = phpdoc_parse_type(type_str, klass->init_function);
      if (!kphp_error(doc_type,
                      format("Failed to parse phpdoc of %s::$%s", klass->name.c_str(), field->local_name().c_str()))) {
        doc_type->location = field->root->location;
        v->type_rule = VertexAdaptor<op_lt_type_rule>::create(doc_type);
      }
    }
  }
}

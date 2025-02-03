<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

use ILIAS\Setup;
use ILIAS\Setup\Environment;

class ilAccessRBACTemplateAddedObjective implements Setup\Objective
{
    protected string $type;
    protected string $id;
    protected string $description;
    protected array $op_ids;

    public function __construct(string $type, string $id, string $description, array $op_ids = [])
    {
        $this->type = $type;
        $this->id = $id;
        $this->description = $description;
        $this->op_ids = $op_ids;
    }

    public function getHash(): string
    {
        return hash("sha256", self::class);
    }

    public function getLabel(): string
    {
        $op_ids = implode(",", $this->op_ids);
        return "Add rbac template (type=$this->type;id=$this->id;description=$this->description;op_ids=$op_ids)";
    }

    public function isNotable(): bool
    {
        return true;
    }

    public function getPreconditions(Environment $environment): array
    {
        return [
            new ilDatabaseInitializedObjective()
        ];
    }

    public function achieve(Environment $environment): Environment
    {
        $db = $environment->getResource(Environment::RESOURCE_DATABASE);

        $tpl_id = $db->nextId("object_data");
        $values = [
            "obj_id" => ["integer", $tpl_id],
            "type" => ["text", "rolt"],
            "title" => ["text", $this->id],
            "description" => ["text", $this->description],
            "owner" => ["integer", -1],
            "create_date" => ["timestamp", date("Y-m-d H:i:s")],
            "last_update" => ["timestamp", date("Y-m-d H:i:s")]
        ];
        $db->insert("object_data", $values);

        $values = [
            "rol_id" => ["integer", $tpl_id],
            "parent" => ["integer", 8],
            "assign" => ["text", "n"],
            "protected" => ["text", "n"]
        ];
        $db->insert("rbac_fa", $values);

        foreach ($this->op_ids as $op_id) {
            $values = [
                "rol_id" => ["integer", $tpl_id],
                "type" => ["text", $this->type],
                "ops_id" => ["integer", $op_id],
                "parent" => ["integer", 8]
            ];
            $db->insert("rbac_templates", $values);
        }

        return $environment;
    }

    public function isApplicable(Environment $environment): bool
    {
        return (bool) count($this->op_ids);
    }
}

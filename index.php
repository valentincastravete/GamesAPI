<?php

require __DIR__ . '/vendor/autoload.php';
$router = new \Bramus\Router\Router();
const PATH_TO_SQLITE_FILE = 'mhw.db';

$db = SQLiteConnection::getDB();

enum LANGS
{
    case en;
    case ja;
    case fr;
    case it;
    case de;
    case es;
    case pt;
    case pl;
    case ru;
    case ko;
    case zh;
    case ar;
}

$lang = LANGS::en->name;

class SQLiteConnection
{
    /**
     * SQLite3 instance
     * @var SQLite3 
     */
    private static $bd;

    /**
     * return in instance of the PDO object that connects to the SQLite database
     * @return \SQLite3
     */
    public static function getDB()
    {
        if (self::$bd == null) {
            try {
                if (!file_exists(PATH_TO_SQLITE_FILE)) {
                    throw new Exception();
                }
                self::$bd = new SQLite3(PATH_TO_SQLITE_FILE);
            } catch (PDOException $ex) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 500, 'message' => 'Connection to Database Failed.']);
            } catch (Exception $e) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 500, 'message' => 'Connection to Database Failed. Database file not found.']);
            }
        }
        return self::$bd;
    }
}

// CORS
function sendCorsHeaders()
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Authorization');
    header('Access-Control-Allow-Methods: GET');
}

$router->options('/.*', function () {
    sendCorsHeaders();
});
sendCorsHeaders();

// HOME

$router->get('/', function () {
    readfile("home.html");
});

function executeSQL(SQLite3 $db, string $sql): array
{
    $result = $db->query($sql);
    $array = [];
    if ($result && $result->numColumns() > 0) {
        while ($elem = $result->fetchArray(SQLITE3_ASSOC)) {
            $array[] = $elem;
        };
    }
    return $array;
}

$router->mount('/monster_hunter_world', function () use ($db, $router, $lang) {

    // MONSTERS
    $router->get('/([a-z]{2}/)?monsters', function ($language = null) use ($db, $lang) {
        header('Content-Type: application/json');

        $lang = $language != null ? $language : $lang;

        $extra_monster_info = getExtraMonsterInfo($db, $lang);
        $monsters_text = $extra_monster_info[0];
        $monsters_locations = $extra_monster_info[1];
        $locations_text = $extra_monster_info[2];
        $monsters_rewards = $extra_monster_info[3];
        $monsters_rewards_conditions_text = $extra_monster_info[4];
        $items = $extra_monster_info[5];
        $items_text = $extra_monster_info[6];

        // Get monsters
        $monsters_result = $db->query("SELECT * FROM monster ORDER BY order_id");
        $monsters = [];
        if ($monsters_result && $monsters_result->numColumns() > 0) {
            while ($monster = $monsters_result->fetchArray(SQLITE3_ASSOC)) {
                $monster = proccessMonster(
                    $monster,
                    $monsters_text,
                    $monsters_locations,
                    $locations_text,
                    $monsters_rewards,
                    $monsters_rewards_conditions_text,
                    $items,
                    $items_text
                );
                $monsters[] = $monster;
            };
        }

        returnJsonData($monsters);
    });

    // MONSTER
    $router->get('/([a-z]{2}/)?monster/(\d+)', function ($language = null, $id) use ($db, $lang) {
        header('Content-Type: application/json');

        $lang = $language != null ? $language : $lang;

        $extra_monster_info = getExtraMonsterInfo($db, $lang);
        $monsters_text = $extra_monster_info[0];
        $monsters_locations = $extra_monster_info[1];
        $locations_text = $extra_monster_info[2];
        $monsters_rewards = $extra_monster_info[3];
        $monsters_rewards_conditions_text = $extra_monster_info[4];
        $items = $extra_monster_info[5];
        $items_text = $extra_monster_info[6];

        // Get monster
        $monster_result = $db->query("SELECT * FROM monster WHERE id = '" . $id . "'");
        if ($monster_result && $monster_result->numColumns() > 0 && $monster = $monster_result->fetchArray(SQLITE3_ASSOC)) {
            $monster = proccessMonster(
                $monster,
                $monsters_text,
                $monsters_locations,
                $locations_text,
                $monsters_rewards,
                $monsters_rewards_conditions_text,
                $items,
                $items_text
            );
        }
        if (!$monster) {
            $monster = ['error' => 'Monster not found'];
        }

        returnJsonData($monster);
    });


    // ARMORS
    $router->get('/([a-z]{2}/)?armors', function ($language = null) use ($db, $lang) {
        header('Content-Type: application/json');

        $lang = $language != null ? $language : $lang;

        $extra_armor_info = getExtraArmorInfo($db, $lang);
        $armors_text = $extra_armor_info[0];;
        $armors_skills = $extra_armor_info[1];
        $armorsets = $extra_armor_info[2];
        $armorsets_text = $extra_armor_info[3];
        $armorsets_bonus_skills = $extra_armor_info[4];
        $armorset_bonus_text = $extra_armor_info[5];
        $skills_tree = $extra_armor_info[6];
        $skills_tree_text = $extra_armor_info[7];
        $recipes_items = $extra_armor_info[8];
        $items = $extra_armor_info[9];
        $items_text = $extra_armor_info[10];

        // Get armors
        $armors = executeSQL($db, 'SELECT * FROM armor ORDER BY order_id');
        $return_armors = [];
        if (count($armors) > 0) {
            foreach ($armors as $armor) {
                $armor = proccessArmor(
                    $armors,
                    $armor,
                    $armors_text,
                    $armors_skills,
                    $armorsets,
                    $armorsets_text,
                    $armorsets_bonus_skills,
                    $armorset_bonus_text,
                    $skills_tree,
                    $skills_tree_text,
                    $recipes_items,
                    $items,
                    $items_text
                );
                $return_armors[] = $armor;
            };
        }

        returnJsonData($return_armors);
    });

    // ARMOR
    $router->get('/([a-z]{2}/)?armor/(\d+)', function ($language = null, $id) use ($db, $lang) {
        header('Content-Type: application/json');

        $lang = $language != null ? $language : $lang;

        $extra_armor_info = getExtraArmorInfo($db, $lang);
        $armors_text = $extra_armor_info[0];;
        $armors_skills = $extra_armor_info[1];
        $armorsets = $extra_armor_info[2];
        $armorsets_text = $extra_armor_info[3];
        $armorsets_bonus_skills = $extra_armor_info[4];
        $armorset_bonus_text = $extra_armor_info[5];
        $skills_tree = $extra_armor_info[6];
        $skills_tree_text = $extra_armor_info[7];
        $recipes_items = $extra_armor_info[8];
        $items = $extra_armor_info[9];
        $items_text = $extra_armor_info[10];

        // Get armors
        $armor = [];
        $armors = executeSQL($db, 'SELECT * FROM armor ORDER BY order_id');
        if ($armors != null && count($armors) > 0) {

            // Get armor
            $armor_result = $db->query("SELECT * FROM armor WHERE id = '" . $id . "'");
            if ($armor_result && $armor_result->numColumns() > 0 && $armor = $armor_result->fetchArray(SQLITE3_ASSOC)) {
                $armor = proccessArmor(
                    $armors,
                    $armor,
                    $armors_text,
                    $armors_skills,
                    $armorsets,
                    $armorsets_text,
                    $armorsets_bonus_skills,
                    $armorset_bonus_text,
                    $skills_tree,
                    $skills_tree_text,
                    $recipes_items,
                    $items,
                    $items_text
                );
            }
            if (!$armor) {
                $armor = ['error' => 'Armor not found'];
            }
        } else {
            $armor = ['error' => 'There is no armor at all'];
        }

        returnJsonData($armor);
    });

    // WEAPONS
    $router->get('/([a-z]{2}/)?weapons', function ($language = null) use ($db, $lang) {
        header('Content-Type: application/json');

        $lang = $language != null ? $language : $lang;

        // Get weapons
        $weapons = executeSQL($db, 'SELECT * FROM weapon ORDER BY order_id');

        returnJsonData($weapons);
    });

    // WEAPON
    $router->get('/([a-z]{2}/)?weapon/(\d+)', function ($language = null, $id) use ($db, $lang) {
        header('Content-Type: application/json');

        $lang = $language != null ? $language : $lang;

        // Get weapon
        $weapon = executeSQL($db, "SELECT * FROM weapon WHERE id = '" . $id . "'");

        returnJsonData($weapon);
    });

    // QUESTS
    $router->get('/([a-z]{2}/)?quests', function ($language = null) use ($db, $lang) {
        header('Content-Type: application/json');

        $lang = $language != null ? $language : $lang;

        // Get quests
        $quests = executeSQL($db, "SELECT q.id as id, q.order_id as order_id, q.category as category, q.rank as rank, q.stars as stars, q.stars_raw as stars_raw, q.quest_type as quest_type, l.name as location, q.zenny as zenny  FROM quest as q JOIN location_text as l ON q.location_id = l.id WHERE l.lang_id = '" . $lang . "' ORDER BY q.order_id");

        returnJsonData($quests);
    });

    // QUEST
    $router->get('/([a-z]{2}/)?quest/(\d+)', function ($language = null, $id) use ($db, $lang) {
        header('Content-Type: application/json');

        $lang = $language != null ? $language : $lang;

        // Get quest
        $quest = executeSQL($db, "SELECT q.id as id, q.order_id as order_id, q.category as category, q.rank as rank, q.stars as stars, q.stars_raw as stars_raw, q.quest_type as quest_type, l.name as location, q.zenny as zenny  FROM quest as q JOIN location_text as l ON q.location_id = l.id WHERE q.id = '" . $id . "' AND l.lang_id = '" . $lang . "' ORDER BY q.order_id");

        returnJsonData($quest);
    });

    // SKILLS
    $router->get('/([a-z]{2}/)?skills', function ($language = null) use ($db, $lang) {
        header('Content-Type: application/json');

        $lang = $language != null ? $language : $lang;

        // Get skills
        $skills = executeSQL($db, "SELECT skilltree_id as id, level, description FROM skill WHERE lang_id = '" . $lang . "'");

        returnJsonData($skills);
    });

    // SKILL
    $router->get('/([a-z]{2}/)?skill/(\d+)', function ($language = null, $id) use ($db, $lang) {
        header('Content-Type: application/json');

        $lang = $language != null ? $language : $lang;

        // Get skill
        $skill = executeSQL($db, "SELECT skilltree_id as id, level, description FROM skill WHERE id = '" . $id . "' AND lang_id = '" . $lang . "'");

        returnJsonData($skill);
    });

    // TOOLS
    $router->get('/([a-z]{2}/)?tools', function ($language = null) use ($db, $lang) {
        header('Content-Type: application/json');

        $lang = $language != null ? $language : $lang;

        // Get tools
        $tools = executeSQL($db, 'SELECT * FROM tool ORDER BY order_id');

        returnJsonData($tools);
    });

    // TOOL
    $router->get('/([a-z]{2}/)?tool/(\d+)', function ($language = null, $id) use ($db, $lang) {
        header('Content-Type: application/json');

        $lang = $language != null ? $language : $lang;

        // Get tool
        $tool = executeSQL($db, "SELECT * FROM tool WHERE id = '" . $id . "'");

        returnJsonData($tool);
    });

    // DECORATIONS
    $router->get('/([a-z]{2}/)?decorations', function ($language = null) use ($db, $lang) {
        header('Content-Type: application/json');

        $lang = $language != null ? $language : $lang;

        // Get decorations
        $decorations = executeSQL($db, 'SELECT * FROM decoration');

        returnJsonData($decorations);
    });

    // DECORATION
    $router->get('/([a-z]{2}/)?decoration/(\d+)', function ($language = null, $id) use ($db, $lang) {
        header('Content-Type: application/json');

        $lang = $language != null ? $language : $lang;

        // Get decoration
        $decoration = executeSQL($db, "SELECT * FROM decoration WHERE id = '" . $id . "'");

        returnJsonData($decoration);
    });

    // CHARMS
    $router->get('/([a-z]{2}/)?charms', function ($language = null) use ($db, $lang) {
        header('Content-Type: application/json');

        $lang = $language != null ? $language : $lang;

        // Get charms
        $charms = executeSQL($db, 'SELECT * FROM charm ORDER BY order_id');

        returnJsonData($charms);
    });

    // CHARM
    $router->get('/([a-z]{2}/)?charm/(\d+)', function ($language = null, $id) use ($db, $lang) {
        header('Content-Type: application/json');

        $lang = $language != null ? $language : $lang;

        // Get charm
        $charm = executeSQL($db, "SELECT * FROM charm WHERE id = '" . $id . "'");

        returnJsonData($charm);
    });

    // CRAFTS
    $router->get('/([a-z]{2}/)?crafts', function ($language = null) use ($db, $lang) {
        header('Content-Type: application/json');

        $lang = $language != null ? $language : $lang;

        // Get crafts
        $crafts = executeSQL($db, 'SELECT * FROM recipe_item');

        returnJsonData($crafts);
    });

    // CRAFT
    $router->get('/([a-z]{2}/)?craft/(\d+)', function ($language = null, $id) use ($db, $lang) {
        header('Content-Type: application/json');

        $lang = $language != null ? $language : $lang;

        // Get craft
        $craft = executeSQL($db, "SELECT * FROM recipe_item WHERE recipe_id = '" . $id . "'");

        returnJsonData($craft);
    });
});

function getExtraMonsterInfo(SQLite3 $db, string $lang): array
{
    // Get monsters text
    $monsters_text = executeSQL($db, "SELECT * FROM monster_text WHERE lang_id = '" . $lang . "'");
    // Get monsters locations
    $monsters_locations = executeSQL($db, "SELECT * FROM monster_habitat");
    // Get locations text
    $locations_text = executeSQL($db, "SELECT id, name FROM location_text WHERE lang_id = '" . $lang . "'");
    // Get monsters rewards
    $monsters_rewards = executeSQL($db, "SELECT * FROM monster_reward");
    // Get rewards conditions text
    $monsters_rewards_conditions_text = executeSQL($db, "SELECT id, name FROM monster_reward_condition_text WHERE lang_id = '" . $lang . "'");
    // Get items
    $items = executeSQL($db, "SELECT * FROM item");
    // Get items text
    $items_text = executeSQL($db, "SELECT id, name, description FROM item_text WHERE lang_id = '" . $lang . "'");

    return [
        $monsters_text,
        $monsters_locations,
        $locations_text,
        $monsters_rewards,
        $monsters_rewards_conditions_text,
        $items,
        $items_text
    ];
}

function getExtraArmorInfo(SQLite3 $db, string $lang): array
{
    // Get armors text
    $armors_text = executeSQL($db, "SELECT id, name FROM armor_text WHERE lang_id = '" . $lang . "'");
    // Get armors skills
    $armors_skills = executeSQL($db, "SELECT * FROM armor_skill");
    // Get armorsets
    $armorsets = executeSQL($db, "SELECT id FROM armorset");
    // Get armorsets text
    $armorsets_text = executeSQL($db, "SELECT id, name FROM armorset_text WHERE lang_id = '" . $lang . "'");
    // Get armorsets bonus skills
    $armorsets_bonus_skills = executeSQL($db, "SELECT * FROM armorset_bonus_skill");
    // Get armorsets bonus skills text
    $armorset_bonus_text = executeSQL($db, "SELECT id, name FROM armorset_bonus_text WHERE lang_id = '" . $lang . "'");
    // Get skills tree
    $skills_tree = executeSQL($db, "SELECT id, max_level FROM skilltree");
    // Get skills tree text
    $skills_tree_text = executeSQL($db, "SELECT id, name, description FROM skilltree_text WHERE lang_id = '" . $lang . "'");
    // Get recipes items
    $recipes_items = executeSQL($db, "SELECT * FROM recipe_item");
    // Get items
    $items = executeSQL($db, "SELECT * FROM item");
    // Get items text
    $items_text = executeSQL($db, "SELECT id, name, description FROM item_text WHERE lang_id = '" . $lang . "'");

    return [
        $armors_text,
        $armors_skills,
        $armorsets,
        $armorsets_text,
        $armorsets_bonus_skills,
        $armorset_bonus_text,
        $skills_tree,
        $skills_tree_text,
        $recipes_items,
        $items,
        $items_text
    ];
}

function proccessMonster(
    array $monster,
    array $monsters_text,
    array $monsters_locations,
    array $locations_text,
    array $monsters_rewards,
    array $monsters_rewards_conditions_text,
    array $items,
    array $items_text
): array {
    if (!empty($monsters_text)) {
        $monster_info = insertAdditionalInfoToMonster($monster, $monsters_text);
        $monster = $monster_info[0];
        $monster_text = $monster_info[1];
    }

    $result_fields = splitMonsterFields($monster);
    $monster = $result_fields[0];
    $removed_fields = $result_fields[1];

    $monster['traps'] = setMonsterTraps($removed_fields);
    $monster['ailments'] = setMonsterAilments($removed_fields);
    $monster['weaknesses'] = setMonsterWeaknesses($removed_fields, $monster_text);

    if (!empty($monsters_locations) && !empty($locations_text)) {
        $monster['locations'] = proccessMonsterLocations($monster, $monsters_locations, $locations_text);
    }
    if (!empty($monsters_rewards) && !empty($monsters_rewards_conditions_text)) {
        $monster['rewards'] = proccessMonstersRewards($monster, $monsters_rewards, $monsters_rewards_conditions_text, $items, $items_text);
    }
    return $monster;
}

function proccessArmor(
    array $armors,
    array $armor,
    array $armors_text,
    array $armors_skills,
    array $armorsets,
    array $armorsets_text,
    array $armorsets_bonus_skills,
    array $armorset_bonus_text,
    array $skills_tree,
    array $skills_tree_text,
    array $recipes_items,
    array $items,
    array $items_text
): array {
    if (!empty($armors_text)) {
        $armor_info = insertAdditionalInfoToArmor($armor, $armors_text);
        $armor = $armor_info[0];
        $armor_text = $armor_info[1];
    }

    $result_fields = splitArmorFields($armor);
    $armor = $result_fields[0];
    $removed_fields = $result_fields[1];

    $armor['armorset'] = proccessArmorArmorset(
        $armors,
        $removed_fields,
        $armorsets,
        $armorsets_text,
        $armors_text,
        $armorsets_bonus_skills,
        $armorset_bonus_text,
        $skills_tree,
        $skills_tree_text,
    );
    $armor['skills'] = processArmorSkills($armor, $armors_skills, $skills_tree, $skills_tree_text);
    // $monster['crafting'] = setMonsterWeaknesses($removed_fields, $monsters_text);
    // $monster['genders'] = setMonsterWeaknesses($removed_fields, $monsters_text);
    // $monster['slots'] = setMonsterWeaknesses($removed_fields, $monsters_text);
    // $monster['defense'] = setMonsterWeaknesses($removed_fields, $monsters_text);
    // $monster['resistances'] = setMonsterWeaknesses($removed_fields, $monsters_text);

    $armor = array_set_values_from_array($armor, $armor, ['type'], ['armor_type']);
    $armor = array_remove($armor, ['armor_type']);
    $armor = array_insert_between($armor, ['type' => $armor['type']], 0, 2, 2, null, true);

    return $armor;
}

function insertAdditionalInfoToMonster(array $monster, array $monsters_text): array
{
    // Get text of monster
    $monster_text = getRecordFromValues($monster, $monsters_text, 'id', 'id', true);
    // Insert additional info to monster (name, descripction, ecology)
    return [insertInfoIntoArray($monster, $monster_text, 'size', ['name', 'description', 'ecology']), $monster_text];
}

function insertAdditionalInfoToArmor(array $armor, array $armors_text): array
{
    // Get text of armor
    $armor_text = getRecordFromValues($armor, $armors_text, 'id', 'id', true);
    // Insert additional info to armor (name)
    return [insertInfoIntoArray($armor, $armor_text, 'order_id', ['name']), $armor_text];
}

function splitMonsterFields(array $monster): array
{
    //Extract and remove monster fields
    return splitArray($monster, 'pitfall_trap', ['order_id']);
}

function splitArmorFields(array $armor): array
{
    //Extract and remove armor fields
    return splitArray($armor, 'armorset_id', ['order_id']);
}

function setMonsterTraps(array $removed_fields): array
{
    // Get traps
    $traps = array_get_items_ending_with($removed_fields, '_trap', true);
    // Rename keys without _traps
    $traps = array_replace_keys_names($traps, '_trap', '');
    return $traps;
}

function setMonsterAilments(array $removed_fields): array
{
    // Get ailments
    $ailments  = array_get_items_starting_with($removed_fields, 'ailment_', false);
    // Rename keys without ailment_
    $ailments = array_replace_keys_names($ailments, 'ailment_', '');
    return $ailments;
}

function setMonsterWeaknesses(array $removed_fields, array $monster_text): array
{
    $weaknesses = [];
    if ($removed_fields['has_weakness']) {
        // Get weaknesses
        $weaknesses  = array_get_items_starting_with($removed_fields, 'weakness_', true);
        // Rename keys without weakness_
        $weaknesses = array_replace_keys_names($weaknesses, 'weakness_', '');
    }

    $alt_weaknesses = [];
    if ($removed_fields['has_alt_weakness']) {
        // Get alt_weaknesses
        $alt_weaknesses  = array_get_items_starting_with($removed_fields, 'alt_weakness_', true);
        // Rename keys without alt_weaknesses_
        $alt_weaknesses = array_replace_keys_names($alt_weaknesses, 'alt_weakness_', '');
        // Add condition alt_weakness
        $alt_weaknesses = ['condition' => $monster_text['alt_state_description']] + $alt_weaknesses;
    }
    return $removed_fields['has_alt_weakness'] ? array_merge($weaknesses, ['alt_weaknesses' => $alt_weaknesses]) : $weaknesses;
}

function proccessMonsterLocations(array $monster, array $monsters_locations, array $locations_text): array
{
    // Get monster locations
    $monster_locations = getRecordFromValues($monster, $monsters_locations, 'id', 'monster_id', false);

    // Set locations names
    $location_loop_count = 0;
    foreach ($monster_locations as $key => $location) {
        $location_name = getRecordFromValues($location, $locations_text, 'location_id', 'id', true, ['name']);

        $monster_locations[$key] = array_set_values_from_array($monster_locations[$key], $location, ['id'], ['location_id']);
        $monster_locations[$key] = array_remove($monster_locations[$key], ['location_id', 'monster_id']);

        $monster_locations[$key] = array_insert_between($monster_locations[$key], $location_name, 0, 1, 1, null);
        if ($location_loop_count != $key) {
            $monster_locations = array_set_values_from_array($monster_locations, $monster_locations, [$location_loop_count++], [$key]);
            $monster_locations = array_remove($monster_locations, [$key]);
        }
    }

    return $monster_locations;
}

function proccessMonstersRewards(array $monster, array $monsters_rewards, array $monsters_rewards_conditions_text, array $items, array $items_text): array
{
    // Get monster rewards
    $monster_rewards = getRecordFromValues($monster, $monsters_rewards, 'id', 'monster_id', false);

    // Set rewards conditions
    $reward_loop_count = 0;
    foreach ($monster_rewards as $key => $reward) {

        $monster_rewards[$key] = getRecordFromValues($reward, $monsters_rewards, 'id', 'id', true, ['id', 'rank', 'stack', 'percentage']);
        $condition_name = getRecordFromValues($reward, $monsters_rewards_conditions_text, 'condition_id', 'id', true, ['name']);

        if (!empty($items) && !empty($items_text)) {
            $monster_rewards[$key]['item'] = getRecordFromValues($reward, $items_text, 'item_id', 'id', true, ['name', 'description']);
        }
        $monster_rewards[$key] = array_set_values_from_array($monster_rewards[$key], $reward, ['id'], ['id']);
        $monster_rewards[$key] = array_set_values_from_array($monster_rewards[$key], $condition_name, ['condition'], ['name']);

        if ($reward_loop_count != $key) {
            $monster_rewards = array_set_values_from_array($monster_rewards, $monster_rewards, [$reward_loop_count], [$key]);
            $monster_rewards = array_remove($monster_rewards, [$key]);
        }
        $monster_rewards[$reward_loop_count] = array_insert_between($monster_rewards[$reward_loop_count], array_slice($monster_rewards[$reward_loop_count], 4, 2, false), 0, 1, 1, 3);
        $reward_loop_count++;
    }

    return $monster_rewards;
}

function proccessArmorArmorset(
    array $armors,
    array $armor,
    array $armorsets,
    array $armorsets_text,
    array $armors_text,
    array $armorsets_bonus_skills,
    array $armorset_bonus_text,
    array $skills_tree,
    array $skills_tree_text,
): array {
    // Get armor armorset
    $armor_armorset = getRecordFromValues($armor, $armorsets, 'armorset_id', 'id', true);

    // Set armorset name
    $armorset_name = getRecordFromValues($armor_armorset, $armorsets_text, 'id', 'id', true, ['name']);
    $armor_armorset = array_insert_between($armor_armorset, $armorset_name, 0, 1, 1, null);

    // Get armorset pieces
    $armorset_pieces = getRecordFromValues($armor_armorset, $armors, 'id', 'armorset_id', false);
    // Get armorset pieces text
    $armorset_pieces_text = [];
    foreach ($armorset_pieces as $key => $armorset_piece) {
        $armorset_pieces_text[] = getRecordFromValues($armorset_piece, $armors_text, 'id', 'id', true, ['id', 'name']);
    }

    // Set armorset pieces
    $fields_from_pieces = ['id', 'armor_type'];
    foreach ($armorset_pieces as $key => $armorset_piece) {
        $armorset_pieces[$key] = array_keep($armorset_pieces[$key], ['id', 'armor_type']);
        $armorset_pieces[$key] = array_filter($armorset_pieces[$key], function ($key) use ($fields_from_pieces) {
            return in_array($key, $fields_from_pieces);
        }, ARRAY_FILTER_USE_KEY);
        $armorset_piece_text = current(array_filter($armorset_pieces_text, function ($value, $key_i) use ($armorset_pieces, $key) {
            return $value['id'] == $armorset_pieces[$key]['id'];
        }, ARRAY_FILTER_USE_BOTH));
        $armorset_pieces[$key] = array_set_values_from_array($armorset_pieces[$key], $armorset_pieces[$key], ['type'], ['armor_type']);
        $armorset_pieces[$key] = array_remove($armorset_pieces[$key], ['armor_type']);
        $armorset_pieces[$key] = insertInfoIntoArray($armorset_pieces[$key], $armorset_piece_text, 'type', ['name']);
    }
    $armor_armorset['pieces'] = $armorset_pieces;

    if (array_key_exists('armorset_bonus_id', $armor) && $armor['armorset_bonus_id'] != null) {
        // Get armorset bonus skill
        $armorset_bonus_skills = getRecordFromValues($armor, $armorsets_bonus_skills, 'armorset_bonus_id', 'setbonus_id', false);
        // Get armorset bonus skills names
        $armorset_skills_loop_count = 0;
        foreach ($armorset_bonus_skills as $key => $armorset_bonus_skill) {
            // Get armorset bonus skill name
            $armorset_bonus_skill_name = getRecordFromValues($armorset_bonus_skills[$key], $armorset_bonus_text, 'setbonus_id', 'id');
            // Set armorset bonus skill name
            $armorset_bonus_skills[$key] = array_set_values_from_array($armorset_bonus_skills[$key], $armorset_bonus_skill_name, ['name'], ['name']);

            // Get skill
            $armorset_skill = getRecordFromValues($armorset_bonus_skills[$key], $skills_tree, 'skilltree_id', 'id', true, ['id', 'max_level']);
            // Get skill name
            $armorset_skill_name = getRecordFromValues($armorset_skill, $skills_tree_text, 'id', 'id', true, ['name', 'description']);
            // Set skill name
            $armorset_skill = array_set_values_from_array($armorset_skill, $armorset_skill_name, ['name', 'description'], ['name', 'description']);
            // Set skill
            $armorset_bonus_skills[$key]['skill'] = $armorset_skill;

            $armorset_bonus_skills[$key] = array_set_values_from_array(
                $armorset_bonus_skills[$key],
                $armorset_bonus_skills[$key],
                ['id', 'required_pieces'],
                ['setbonus_id', 'required']
            );
            $armorset_bonus_skills[$key] = array_remove($armorset_bonus_skills[$key], ['setbonus_id', 'skilltree_id', 'required']);

            if ($armorset_skills_loop_count != $key) {
                $armorset_bonus_skills = array_set_values_from_array($armorset_bonus_skills, $armorset_bonus_skills, [$armorset_skills_loop_count], [$key]);
                $armorset_bonus_skills = array_remove($armorset_bonus_skills, [$key]);
            }
            $armorset_bonus_skills[$armorset_skills_loop_count] = array_insert_between(
                $armorset_bonus_skills[$armorset_skills_loop_count],
                array_slice(
                    $armorset_bonus_skills[$armorset_skills_loop_count],
                    2,
                    1,
                    false
                ),
                0,
                0,
                0,
                null
            );
            $armorset_skills_loop_count++;
        }
        $armor_armorset['bonus_skill'] = $armorset_bonus_skills;
    }

    return $armor_armorset;
}

function processArmorSkills(array $armor, array $armors_skills, array $skills_tree, array $skills_tree_text): array
{
    // Get armor skills
    $armor_skills = getRecordFromValues($armor, $armors_skills, 'id', 'armor_id', false);

    if ($armor_skills && !empty($armor_skills)) {
        // Set armor skills info
        $armor_skills_loop_count = 0;
        foreach ($armor_skills as $key => $armor_skill) {
            // Get skill
            $skill = getRecordFromValues($armor_skills[$key], $skills_tree, 'skilltree_id', 'id', true);
            // Get skill info
            $skill_info = getRecordFromValues($armor_skills[$key], $skills_tree_text, 'skilltree_id', 'id', true);

            $armor_skills[$key] = array_remove($armor_skills[$key], ['armor_id', 'skilltree_id']);
            $armor_skills[$key] = array_merge($skill_info, $armor_skills[$key], $skill);

            if ($armor_skills_loop_count != $key) {
                $armor_skills = array_set_values_from_array($armor_skills, $armor_skills, [$armor_skills_loop_count], [$key]);
                $armor_skills = array_remove($armor_skills, [$key]);
            }
            $armor_skills_loop_count++;
        }
    }

    $armor['skills'] = $armor_skills;

    return $armor_skills;
}

function getRecordFromValues(array $record, array $values, string $idFromRecord, string $idFromValues, bool $singleValue = true, array $fields = null): mixed
{
    $value_record = array_filter($values,  function ($value, $key) use ($record, $idFromRecord, $idFromValues) {
        return array_key_exists($idFromValues, $value) && array_key_exists($idFromRecord, $record) ? $value[$idFromValues] === $record[$idFromRecord] : false;
    }, ARRAY_FILTER_USE_BOTH);
    $value_record = !$value_record ? [] : ($singleValue ? current($value_record) : $value_record);
    if ($fields != null && is_array($value_record) && count($value_record) > 0 && !is_array(array_values($value_record)[0])) {
        return array_filter($value_record, function ($key) use ($fields) {
            return in_array($key, $fields);
        }, ARRAY_FILTER_USE_KEY);
    }
    return $value_record;
}

function returnJsonData(array $data)
{
    echo json_encode($data);
}


//404
$router->set404(function () {
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: application/json');

    $jsonArray = array();
    $jsonArray['status'] = "404";
    $jsonArray['status_text'] = "Route not defined";

    echo (json_encode($jsonArray));
});

function insertInfoIntoArray(array $array, array $array_info, string|int $field_index_to_insert, array $fields_to_add = []): array
{
    if (!empty($array_info)) {
        $insert_values = [];
        foreach ($fields_to_add as $field) {
            $insert_values = array_merge($insert_values, [$field => $array_info[$field]]);
        }

        $insert_index = array_search($field_index_to_insert, array_keys($array));
        $array = array_insert_between($array, $insert_values, 0, $insert_index, $insert_index, null, true);
    }
    return $array;
}

function array_remove(array $array, array $keys): array
{
    return array_diff_key($array, array_flip((array) $keys));
}

function array_keep(array $array, array $keys): array
{
    return array_filter($array, function ($key) use ($keys) {
        return in_array($key, $keys);
    }, ARRAY_FILTER_USE_KEY);
}

function array_get_items_starting_with(array $array, string $needle, bool $include_zeros)
{
    return array_filter($array, function ($value, $key) use ($needle, $include_zeros) {
        $if_zero = $include_zeros ? $value !== null : $value != null;
        return $if_zero && str_starts_with($key, $needle);
    }, ARRAY_FILTER_USE_BOTH);
}

function array_get_items_ending_with(array $array, string $needle, bool $include_zeros)
{
    return array_filter($array, function ($value, $key) use ($needle, $include_zeros) {
        $if_zero = $include_zeros ? $value !== null : $value != null;
        return $if_zero && str_ends_with($key, $needle);
    }, ARRAY_FILTER_USE_BOTH);
}

function array_replace_keys_names(array $array, string $search, string $replace): array
{
    foreach (array_keys($array) as $old_key) {
        $new_key = str_replace($search, $replace, $old_key);
        $array[$new_key] = $array[$old_key];
        $array = array_remove($array, [$old_key]);
    }
    return $array;
}

function splitArray(array $array, string $index_name, array $fields_to_remove_from_array = [], array $fields_to_remove_from_splitted_array = []): array
{
    $extract_index = array_search($index_name, array_keys($array));
    $fields_to_keep = array_slice($array, 0, $extract_index, true);
    $fields_to_remove = array_slice($array, $extract_index, null, true);
    $keept_fields = array_remove($fields_to_keep, $fields_to_remove_from_array);
    $removed_fields = array_remove($fields_to_remove, $fields_to_remove_from_splitted_array);
    return [$keept_fields, $removed_fields];
}

function array_insert_between(array $array, array $array_to_insert, int $offset_start, int $length_start = null, int $offset_end, int $length_end = null, bool $preserve_keys = false): array
{
    return array_merge(array_slice($array, $offset_start, $length_start, $preserve_keys), $array_to_insert, array_slice($array, $offset_end, $length_end, $preserve_keys));
}

function array_set_values_from_array(array $array, array $array_from, array $array_keys, array $array_from_keys): array
{
    for ($i = 0; $i < count($array_keys); $i++) {
        if (array_key_exists($array_from_keys[$i], $array_from)) {
            $array[$array_keys[$i]] = $array_from[$array_from_keys[$i]];
        }
    }
    return $array;
}

$router->run();

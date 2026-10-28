<?php
function contact_form(array $row, string $button): string
{
    $fields = [
        'surname' => ['Фамилия', 'text'], 'name' => ['Имя', 'text'],
        'patronymic' => ['Отчество', 'text'], 'birth_date' => ['Дата рождения', 'date'],
        'phone' => ['Телефон', 'tel'], 'address' => ['Адрес', 'text'],
        'email' => ['Email', 'email'],
    ];
    $html = '<form method="post" action="" class="contact-form"><input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
    foreach ($fields as $key => [$label, $type]) {
        if ($key === 'birth_date') {
            $html .= '<div class="field"><label for="birth_date">Дата рождения</label><input id="birth_date" type="date" name="birth_date" value="' . e($row['birth_date'] ?? '') . '"></div>';
            continue;
        }
        $required = in_array($key, ['surname','name'], true) ? ' required' : '';
        $max = ['surname'=>100,'name'=>100,'patronymic'=>100,'phone'=>50,'address'=>255,'email'=>254][$key];
        $html .= '<div class="field"><label for="' . $key . '">' . $label . ($required ? ' <span class="required">*</span>' : '') . '</label><input id="' . $key . '" type="' . $type . '" name="' . $key . '" value="' . e($row[$key] ?? '') . '" maxlength="' . $max . '"' . $required . '></div>';
        if ($key === 'patronymic') {
            $html .= '<div class="field"><label for="gender">Пол</label><select id="gender" name="gender">';
            foreach ([''=>'Не указан','мужской'=>'Мужской','женский'=>'Женский'] as $value=>$text) {
                $selected = ($row['gender'] ?? '') === $value ? ' selected' : '';
                $html .= '<option value="' . e($value) . '"' . $selected . '>' . $text . '</option>';
            }
            $html .= '</select></div>';
        }
    }
    $html .= '<div class="field field-wide"><label for="comment">Комментарий</label><textarea id="comment" name="comment" rows="4" maxlength="2000">' . e($row['comment'] ?? '') . '</textarea></div>';
    return $html . '<div class="field-wide"><button class="submit-btn" type="submit" name="save" value="1">' . e($button) . '</button></div></form>';
}

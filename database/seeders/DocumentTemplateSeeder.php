<?php

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DocumentTemplate::create([
            'name' => 'Договор подряда (упрощенный)',
            'content' => [
                'metadata' => [
                    'title' => 'Договор подряда',
                    'version' => '1.0'
                ],
                'structure' => [
                    [
                        'id' => 'header',
                        'type' => 'section',
                        'enabled' => true,
                        'elements' => [
                            [
                                'id' => 'header_title',
                                'type' => 'heading',
                                'content' => 'ДОГОВОР ПОДРЯДА № <span class="placeholder" data-variable="contract_number">[номер]</span>'
                            ],
                            [
                                'id' => 'header_date',
                                'type' => 'html',
                                'content' => '<table style="width: 100%; border: none; margin: 20px 0;">
                        <tr>
                            <td style="width: 50%; border: none;">г. <span class="placeholder" data-variable="city">[город]</span></td>
                            <td style="width: 50%; border: none; text-align: right;">«<span class="placeholder" data-variable="day">[день]</span>» <span class="placeholder" data-variable="month">[месяц]</span> <span class="placeholder" data-variable="year">[год]</span> г.</td>
                        </tr>
                    </table>',
                                'variables' => [
                                    [
                                        'name' => 'city',
                                        'type' => 'select',
                                        'label' => 'Город',
                                        'options' => [
                                            'Москва' => 'г. Москва',
                                            'Санкт-Петербург' => 'г. Санкт-Петербург',
                                            'Новосибирск' => 'г. Новосибирск',
                                            'Екатеринбург' => 'г. Екатеринбург'
                                        ],
                                        'default' => 'Москва'
                                    ],
                                    'day', 'month', 'year' // простые текстовые поля
                                ]
                            ]
                        ]
                    ],
                    [
                        'id' => 'preamble',
                        'type' => 'section',
                        'enabled' => true,
                        'elements' => [
                            [
                                'id' => 'preamble_title',
                                'type' => 'heading',
                                'content' => '1. ПРЕАМБУЛА'
                            ],
                            [
                                'id' => 'preamble_text',
                                'type' => 'html',
                                'content' => '<p><span class="placeholder" data-variable="client_type">[Тип заказчика]</span> <span class="placeholder" data-variable="client_name">[Ф.И.О. или наименование Заказчика]</span>, именуемый в дальнейшем «Заказчик», с одной стороны, и <span class="placeholder" data-variable="contractor_type">[Тип подрядчика]</span> <span class="placeholder" data-variable="contractor_name">[Ф.И.О. или наименование Подрядчика]</span>, именуемый в дальнейшем «Подрядчик», с другой стороны, заключили настоящий договор о нижеследующем:</p>',
                                'variables' => [
                                    [
                                        'name' => 'client_type',
                                        'type' => 'select',
                                        'label' => 'Тип заказчика',
                                        'options' => [
                                            'Индивидуальный предприниматель' => 'Индивидуальный предприниматель',
                                            'Общество с ограниченной ответственностью' => 'Общество с ограниченной ответственностью',
                                            'Физическое лицо' => 'Физическое лицо'
                                        ],
                                        'default' => 'Индивидуальный предприниматель'
                                    ],
                                    'client_name',
                                    [
                                        'name' => 'contractor_type',
                                        'type' => 'select',
                                        'label' => 'Тип подрядчика',
                                        'options' => [
                                            'Индивидуальный предприниматель' => 'Индивидуальный предприниматель',
                                            'Общество с ограниченной ответственностью' => 'Общество с ограниченной ответственностью',
                                            'Физическое лицо' => 'Физическое лицо'
                                        ],
                                        'default' => 'Индивидуальный предприниматель'
                                    ],
                                    'contractor_name'
                                ]
                            ]
                        ]
                    ],
                    [
                        'id' => 'subject',
                        'type' => 'section',
                        'enabled' => true,
                        'elements' => [
                            [
                                'id' => 'subject_title',
                                'type' => 'heading',
                                'content' => '2. ПРЕДМЕТ ДОГОВОРА'
                            ],
                            [
                                'id' => 'subject_text',
                                'type' => 'html',
                                'content' => '<p>2.1. Подрядчик обязуется выполнить следующие работы: <span class="placeholder" data-variable="work_type">[вид работ]</span>, а Заказчик обязуется принять результат работ и оплатить его.</p>',
                                'variables' => [
                                    [
                                        'name' => 'work_type',
                                        'type' => 'select',
                                        'label' => 'Вид работ',
                                        'options' => [
                                            'ремонтные' => 'ремонтные работы',
                                            'строительные' => 'строительные работы',
                                            'отделочные' => 'отделочные работы',
                                            'монтажные' => 'монтажные работы',
                                            'проектные' => 'проектные работы'
                                        ],
                                        'default' => 'ремонтные'
                                    ]
                                ]
                            ],
                            [
                                'id' => 'subject_details',
                                'type' => 'html',
                                'content' => '<p>2.2. Подробное описание работ: <span class="placeholder" data-variable="work_description">[описание работ]</span>.</p>',
                                'variables' => ['work_description']
                            ]
                        ]
                    ],
                    [
                        'id' => 'payment',
                        'type' => 'section',
                        'enabled' => true,
                        'elements' => [
                            [
                                'id' => 'payment_title',
                                'type' => 'heading',
                                'content' => '3. СТОИМОСТЬ РАБОТ И ПОРЯДОК РАСЧЕТОВ'
                            ],
                            [
                                'id' => 'payment_text',
                                'type' => 'html',
                                'content' => '<p>3.1. Стоимость работ составляет: <span class="placeholder" data-variable="work_price">[сумма]</span> рублей.</p>',
                                'variables' => ['work_price']
                            ],
                            [
                                'id' => 'payment_method',
                                'type' => 'html',
                                'content' => '<p>3.2. Форма оплаты: <span class="placeholder" data-variable="payment_method">[форма оплаты]</span>.</p>',
                                'variables' => [
                                    [
                                        'name' => 'payment_method',
                                        'type' => 'radio',
                                        'label' => 'Форма оплаты',
                                        'options' => [
                                            'cash' => 'Наличный расчет',
                                            'non-cash' => 'Безналичный расчет',
                                            'advance' => 'Авансовый платеж'
                                        ],
                                        'default' => 'non-cash'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'id' => 'signatures',
                        'type' => 'section',
                        'enabled' => true,
                        'elements' => [
                            [
                                'id' => 'signatures_table',
                                'type' => 'html',
                                'content' => '<table style="width: 100%; border-collapse: collapse; margin-top: 40px;">
                                <tr>
                                    <td style="width: 50%; border: none; vertical-align: top;">
                                        <strong>ЗАКАЗЧИК:</strong><br>
                                        <span class="placeholder" data-variable="client_name">[Наименование/Ф.И.О.]</span><br>
                                        Подпись: ________________ / <span class="placeholder" data-variable="client_signature">[Ф.И.О.]</span> /
                                    </td>
                                    <td style="width: 50%; border: none; vertical-align: top;">
                                        <strong>ПОДРЯДЧИК:</strong><br>
                                        <span class="placeholder" data-variable="contractor_name">[Наименование/Ф.И.О.]</span><br>
                                        Подпись: ________________ / <span class="placeholder" data-variable="contractor_signature">[Ф.И.О.]</span> /
                                    </td>
                                </tr>
                            </table>',
                                'variables' => ['client_name', 'client_signature', 'contractor_name', 'contractor_signature']
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }
}

# План реалізації мінімалістичної аналітики Dashboard

## Структура віджетів

### 1. **DatePeriodFilterWidget** (Widget з filter dropdown)
- Один фільтр періоду на весь Dashboard
- Фільтри: Today / 7 days / 30 days / This month / Custom range
- Впливає на всі віджети нижче через shared property

### 2. **MonthlyProfitWidget** (StatsOverviewWidget - 1 картка)
- **Прибуток цього місяця** = 
  - Всі доходи (Sale::sum('sell_price')) 
  - Мінус всі витрати (Batch::sum('total_cost'))
  - Мінус списання (Device/Part зі статусом Broken за місяць)
  - Мінус витрати сервісу (Repair::sum('parts_cost'))
- Один показник, без дублювання

### 3. **ShopAnalyticsWidget** (TableWidget або Custom View)
Блок "Магазин":
- **За обраний період** (2 рядки таблиці):
  - Техніка: сума продажу / сума собівартості / дохід (прибуток)
  - Аксесуари: сума продажу / сума собівартості / дохід (прибуток)
- **В наявності зараз**:
  - Сума техніки в наявності (Device::where('status', 'Stock')->sum('purchase_cost'))
  - Сума аксесуарів в наявності (Part з PartType "Аксесуар" + status Stock -> sum('cost_uah * quantity'))

### 4. **ServiceAnalyticsWidget** (TableWidget або Custom View)
Блок "Сервіс":
- **За обраний період**:
  - Дохід сервісу (Repair::sum('repair_cost'))
  - Витрати сервісу (Repair::sum('parts_cost'))
  - Прибуток сервісу (Repair::sum('profit'))
- **Окремо:**
  - Загальна сума деталей на складі (Part з типом "Деталь" + status Stock -> sum('cost_uah * quantity'))

### 5. **BottomSummaryWidget** (TableWidget або Custom View)
Нижній блок:
- На яку суму було списано товарів за обраний період
  - Device зі статусом Broken + created_at в періоді -> sum('purchase_cost')
  - Part зі статусом Broken + created_at в періоді -> sum('cost_uah * quantity')
- На яку суму інвентарю є в наявності (на зараз)
  - Part з PartType "Інвентар" + status Stock -> sum('cost_uah * quantity')

## Файли для створення/зміни

### Нові файли:
1. `app/Filament/Widgets/DatePeriodFilterWidget.php` - фільтр періоду
2. `app/Filament/Widgets/MonthlyProfitWidget.php` - показник прибутку
3. `app/Filament/Widgets/ShopAnalyticsWidget.php` - блок Магазин
4. `app/Filament/Widgets/ServiceAnalyticsWidget.php` - блок Сервіс
5. `app/Filament/Widgets/BottomSummaryWidget.php` - нижній блок
6. `resources/views/filament/widgets/shop-analytics-widget.blade.php` - view для Магазин
7. `resources/views/filament/widgets/service-analytics-widget.blade.php` - view для Сервіс
8. `resources/views/filament/widgets/bottom-summary-widget.blade.php` - view для нижнього блоку

### Файли для оновлення:
9. `app/Providers/Filament/AdminPanelProvider.php` - приховати AccountWidget, налаштувати віджети
10. `app/Filament/Widgets/DashboardKPIsWidget.php` - приховати (canView: false)
11. `app/Filament/Widgets/DashboardSecondaryKPIsWidget.php` - приховати (вже приховано)
12. `app/Filament/Widgets/DashboardIncomeExpensesChart.php` - приховати (canView: false)
13. `app/Filament/Widgets/TopSalesWidget.php` - приховати (canView: false)
14. `app/Filament/Widgets/StuckInventoryWidget.php` - приховати (canView: false)

## Технічні деталі

### Визначення типів:
- **Техніка**: `Sale::where('saleable_type', Device::class)`
- **Аксесуари**: `Sale::where('saleable_type', Part::class)->whereHas('partType', fn($q) => $q->where('name', 'like', '%Аксесуар%'))`
- **Деталі**: `Part::whereHas('partType', fn($q) => $q->where('name', 'not like', '%Аксесуар%')->where('name', 'not like', '%Інвентар%')->where('name', 'not like', '%Розхідник%'))`
- **Інвентар**: `Part::whereHas('partType', fn($q) => $q->where('name', 'like', '%Інвентар%'))`

### Списання:
- Device/Part зі статусом `Broken`
- Враховувати `created_at` або `updated_at` для визначення періоду списання

### Shared Filter Property:
Використати `protected static ?string $pollingInterval = null;` та загальну властивість через session або static property для синхронізації фільтрів між віджетами.

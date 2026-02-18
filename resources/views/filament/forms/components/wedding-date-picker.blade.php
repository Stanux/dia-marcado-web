<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            value: $wire.$entangle('{{ $getStatePath() }}'),
            currentMonth: null,
            currentYear: null,
            
            init() {
                const today = new Date();
                if (this.value) {
                    const date = new Date(this.value);
                    this.currentMonth = date.getMonth();
                    this.currentYear = date.getFullYear();
                } else {
                    this.currentMonth = today.getMonth();
                    this.currentYear = today.getFullYear();
                }
            },
            
            get monthName() {
                const months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                               'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                return months[this.currentMonth];
            },
            
            get daysInMonth() {
                return new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
            },
            
            get firstDayOfMonth() {
                return new Date(this.currentYear, this.currentMonth, 1).getDay();
            },
            
            get calendarDays() {
                const days = [];
                const totalCells = 42;
                
                // Empty cells before first day
                for (let i = 0; i < this.firstDayOfMonth; i++) {
                    days.push({ day: null, disabled: true });
                }
                
                // Days of month
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                for (let day = 1; day <= this.daysInMonth; day++) {
                    const date = new Date(this.currentYear, this.currentMonth, day);
                    const isPast = date < today;
                    days.push({ 
                        day, 
                        disabled: isPast,
                        date: this.formatDate(date)
                    });
                }
                
                // Fill remaining cells
                while (days.length < totalCells) {
                    days.push({ day: null, disabled: true });
                }
                
                return days;
            },
            
            formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            },
            
            formatDisplayDate(dateStr) {
                if (!dateStr) return '';
                const date = new Date(dateStr + 'T00:00:00');
                return date.toLocaleDateString('pt-BR', { 
                    weekday: 'long', 
                    day: 'numeric', 
                    month: 'long', 
                    year: 'numeric' 
                });
            },
            
            selectDate(dateStr) {
                this.value = dateStr;
            },
            
            isSelected(dateStr) {
                return this.value === dateStr;
            },
            
            prevMonth() {
                if (this.currentMonth === 0) {
                    this.currentMonth = 11;
                    this.currentYear--;
                } else {
                    this.currentMonth--;
                }
            },
            
            nextMonth() {
                if (this.currentMonth === 11) {
                    this.currentMonth = 0;
                    this.currentYear++;
                } else {
                    this.currentMonth++;
                }
            },
            
            prevYear() {
                this.currentYear--;
            },
            
            nextYear() {
                this.currentYear++;
            },
            
            goToToday() {
                const today = new Date();
                this.currentMonth = today.getMonth();
                this.currentYear = today.getFullYear();
            }
        }"
        class="wedding-date-picker h-full w-full"
    >
        <!-- Calendar -->
        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
            style="height: clamp(15rem, 46vh, 20rem);"
        >
            <!-- Year Navigation -->
            <div class="flex items-center justify-between px-2 py-1 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 sm:px-3">
                <button type="button" @click="prevYear" class="p-1 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-md transition">
                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                    </svg>
                </button>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200 sm:text-base" x-text="currentYear"></span>
                <button type="button" @click="nextYear" class="p-1 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-md transition">
                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Month Navigation -->
            <div class="flex items-center justify-between px-2 py-1.5 bg-pink-500 dark:bg-pink-600 sm:px-3">
                <button type="button" @click="prevMonth" class="p-1.5 hover:bg-pink-600 dark:hover:bg-pink-700 rounded-md transition">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <span class="text-base font-semibold text-white sm:text-lg" x-text="monthName"></span>
                <button type="button" @click="nextMonth" class="p-1.5 hover:bg-pink-600 dark:hover:bg-pink-700 rounded-md transition">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>

            <!-- Weekday Headers -->
            <div class="grid grid-cols-7 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                <template x-for="day in ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']">
                    <div class="py-0.5 text-center text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:text-[11px]" x-text="day"></div>
                </template>
            </div>

            <!-- Calendar Grid -->
            <div class="grid grid-cols-7 gap-px bg-gray-200 dark:bg-gray-700">
                <template x-for="(cell, index) in calendarDays" :key="index">
                    <button
                        type="button"
                        @click="!cell.disabled && selectDate(cell.date)"
                        :disabled="cell.disabled || !cell.day"
                        :class="{
                            'bg-white dark:bg-gray-800': !isSelected(cell.date),
                            'bg-pink-500 dark:bg-pink-600 text-white': isSelected(cell.date),
                            'hover:bg-pink-100 dark:hover:bg-pink-900/50': !cell.disabled && cell.day && !isSelected(cell.date),
                            'text-gray-400 dark:text-gray-600 cursor-not-allowed': cell.disabled,
                            'text-gray-700 dark:text-gray-200': !cell.disabled && !isSelected(cell.date),
                            'font-bold': isSelected(cell.date)
                        }"
                        class="h-6 text-center text-[11px] transition-colors sm:h-7 sm:text-sm"
                    >
                        <span x-text="cell.day"></span>
                    </button>
                </template>
            </div>

            <!-- Quick Actions -->
            <div class="flex justify-center gap-2 p-1 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 sm:p-1.5">
                <button 
                    type="button" 
                    @click="goToToday" 
                    class="px-2 py-0.5 text-[11px] text-pink-600 dark:text-pink-400 hover:bg-pink-100 dark:hover:bg-pink-900/30 rounded-md transition sm:text-sm"
                >
                    Ir para hoje
                </button>
            </div>
        </div>
    </div>
</x-dynamic-component>

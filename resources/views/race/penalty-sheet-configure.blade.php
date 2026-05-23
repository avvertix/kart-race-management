<x-app-layout>
    <x-slot name="title">
        {{ $race->title }} - {{ __('Print check sheet') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>

    <div class="px-4 sm:px-6 lg:px-8 py-6">

        @if ($categories->isEmpty())
            <div class="bg-white rounded-lg shadow p-8 text-center text-zinc-500">
                {{ __('No confirmed participants found for this race.') }}
            </div>
        @else

            <script>
                function penaltySheetConfigurator(categories, storageKey) {
                    return {
                        groups: [],
                        nextId: 2,
                        dragging: null,
                        dragOverGroupId: null,

                        init() {
                            this.loadFromStorage(categories);
                            this.$watch('groups', () => this.saveToStorage(), { deep: true });
                        },

                        loadFromStorage(categories) {
                            const stored = localStorage.getItem(storageKey);
                            if (stored) {
                                try {
                                    const parsed = JSON.parse(stored);
                                    const storedUlids = parsed.groups.flatMap(g => g.categories.map(c => c.ulid));
                                    const currentUlids = categories.map(c => c.ulid);
                                    const allPresent = currentUlids.every(u => storedUlids.includes(u));
                                    const noExtra = storedUlids.every(u => currentUlids.includes(u));
                                    if (allPresent && noExtra) {
                                        this.groups = parsed.groups;
                                        this.nextId = parsed.nextId ?? (parsed.groups.length + 1);
                                        return;
                                    }
                                } catch (e) {}
                            }
                            this.groups = [{ id: 1, categories: categories }];
                            this.nextId = 2;
                        },

                        saveToStorage() {
                            localStorage.setItem(storageKey, JSON.stringify({ groups: this.groups, nextId: this.nextId }));
                        },

                        addGroup() {
                            this.groups.push({ id: this.nextId++, categories: [] });
                        },

                        removeGroup(groupId) {
                            this.groups = this.groups.filter(g => g.id !== groupId);
                        },

                        moveCategory(catUlid, fromGroupId, toGroupId) {
                            const fromGroup = this.groups.find(g => g.id === fromGroupId);
                            const toGroup = this.groups.find(g => g.id === toGroupId);
                            const catIndex = fromGroup.categories.findIndex(c => c.ulid === catUlid);
                            const [cat] = fromGroup.categories.splice(catIndex, 1);
                            toGroup.categories.push(cat);
                        },

                        onDragStart(catUlid, fromGroupId, event) {
                            this.dragging = { catUlid, fromGroupId };
                            event.dataTransfer.effectAllowed = 'move';
                        },

                        onDragOver(groupId, event) {
                            if (!this.dragging) { return; }
                            event.preventDefault();
                            event.dataTransfer.dropEffect = 'move';
                            this.dragOverGroupId = groupId;
                        },

                        onDragLeave(groupId) {
                            if (this.dragOverGroupId === groupId) {
                                this.dragOverGroupId = null;
                            }
                        },

                        onDrop(toGroupId, event) {
                            event.preventDefault();
                            if (this.dragging && this.dragging.fromGroupId !== toGroupId) {
                                this.moveCategory(this.dragging.catUlid, this.dragging.fromGroupId, toGroupId);
                            }
                            this.dragging = null;
                            this.dragOverGroupId = null;
                        },

                        onDragEnd() {
                            this.dragging = null;
                            this.dragOverGroupId = null;
                        },

                        get printUrl() {
                            const base = '{{ route('races.penalty-sheet.print', $race) }}';
                            const params = new URLSearchParams();
                            this.groups
                                .filter(g => g.categories.length > 0)
                                .forEach((group, i) => {
                                    group.categories.forEach(cat => {
                                        params.append('groups[' + i + '][]', cat.ulid);
                                    });
                                });
                            const qs = params.toString();
                            return qs ? base + '?' + qs : base;
                        },
                    };
                }
            </script>

            <div
                x-data="penaltySheetConfigurator({{ Js::from($categories->map(fn ($c) => ['ulid' => $c->ulid, 'name' => $c->name])->values()) }}, 'penalty-sheet-groups-{{ $race->ulid }}')"
                class="space-y-6"
            >
                <div>
                    <h3 class="text-lg font-semibold text-zinc-800">{{ __('Print check sheet') }} - {{ __('Define groups') }}</h3>
                    <p class="mt-1 text-sm text-zinc-500">{{ __('Each group will be printed on a separate page. Drag categories between groups or use the arrows. Your arrangement is saved automatically.') }}</p>
                </div>

                <div class="flex flex-wrap gap-4 items-start">

                    <template x-for="(group, gIdx) in groups" :key="group.id">
                        <div
                            class="bg-white rounded-lg border shadow-sm p-4 w-52 transition-colors"
                            :class="dragOverGroupId === group.id ? 'border-orange-400 bg-orange-50' : 'border-zinc-200'"
                            @dragover="onDragOver(group.id, $event)"
                            @dragleave="onDragLeave(group.id)"
                            @drop="onDrop(group.id, $event)"
                        >

                            <h4 class="font-semibold text-sm text-zinc-600 mb-3">
                                {{ __('Group') }}&nbsp;<span x-text="gIdx + 1"></span>
                            </h4>

                            <div class="space-y-2 min-h-10">
                                <template x-for="cat in group.categories" :key="cat.ulid">
                                    <div
                                        class="flex items-center justify-between gap-1 bg-orange-50 border border-orange-200 rounded px-2 py-1.5 cursor-grab active:cursor-grabbing"
                                        :class="dragging && dragging.catUlid === cat.ulid ? 'opacity-40' : ''"
                                        draggable="true"
                                        @dragstart="onDragStart(cat.ulid, group.id, $event)"
                                        @dragend="onDragEnd()"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3 text-zinc-400 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8.5 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM8.5 13.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM8.5 21a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM15.5 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM15.5 13.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM15.5 21a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"/>
                                        </svg>
                                        <span class="text-sm font-medium text-zinc-800 truncate flex-1" x-text="cat.name"></span>
                                        <div class="flex gap-0.5 shrink-0">
                                            <button
                                                type="button"
                                                x-show="gIdx > 0"
                                                @click="moveCategory(cat.ulid, group.id, groups[gIdx - 1].id)"
                                                class="p-0.5 rounded text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100"
                                                title="{{ __('Move to previous group') }}"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                                </svg>
                                            </button>
                                            <button
                                                type="button"
                                                x-show="gIdx < groups.length - 1"
                                                @click="moveCategory(cat.ulid, group.id, groups[gIdx + 1].id)"
                                                class="p-0.5 rounded text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100"
                                                title="{{ __('Move to next group') }}"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <p
                                    x-show="group.categories.length === 0"
                                    class="text-xs text-zinc-400 italic py-1"
                                >{{ __('Empty') }}</p>
                            </div>

                            <button
                                type="button"
                                x-show="group.categories.length === 0"
                                @click="removeGroup(group.id)"
                                class="mt-3 text-xs text-red-500 hover:text-red-700 hover:underline"
                            >{{ __('Remove group') }}</button>

                        </div>
                    </template>

                    <button
                        type="button"
                        @click="addGroup()"
                        class="flex flex-col items-center justify-center gap-2 w-52 min-h-20 rounded-lg border-2 border-dashed border-zinc-300 text-zinc-400 hover:border-orange-400 hover:text-orange-600 text-sm font-medium transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ __('Add group') }}
                    </button>

                </div>

                <div class="pt-2">
                    <a
                        :href="printUrl"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white text-sm font-semibold rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition-colors"
                    >
                        <x-ri-printer-line class="size-4 shrink-0" />
                        {{ __('Print PDF') }}
                    </a>
                </div>

            </div>

        @endif

    </div>

</x-app-layout>

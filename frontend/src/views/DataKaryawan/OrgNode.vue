<script setup>
// Component Self-Reference is implicit in <script setup> if filename is OrgNode.vue
// No special changes needed, just ensure NO Inertia imports.

defineProps({
    user: Object
});

const getInitials = (name) => {
    if (!name) return '';
    return name
        .match(/(\b\S)?/g).join("")
        .match(/(^\S|\S$)?/g).join("")
        .substring(0, 2)
        .toUpperCase();
};
</script>

<template>
    <div class="flex flex-col items-center">
        <div class="z-10 p-4 bg-white rounded-xl shadow-md border-t-4 transition-transform hover:-translate-y-1 w-48 flex flex-col items-center relative"
             :class="{
                 'border-purple-600': user.role === 'HRD',
                 'border-blue-600': user.role === 'Manager',
                 'border-gray-400': user.role === 'Staff'
             }">
            
            <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg mb-2 shadow-sm border-2 border-white"
                 :class="{
                     'bg-purple-600': user.role === 'HRD',
                     'bg-blue-600': user.role === 'Manager',
                     'bg-gray-500': user.role === 'Staff'
                 }">
                {{ getInitials(user.nama) }}
            </div>

            <div class="text-center w-full">
                <h3 class="text-sm font-bold text-gray-800 leading-tight truncate">{{ user.nama }}</h3>
                <p class="text-[10px] uppercase font-bold tracking-wider mt-1 text-gray-500">{{ user.role }}</p>
                <p class="text-[10px] text-gray-400 truncate">{{ user.departemen }}</p>
            </div>
        </div>

        <div v-if="user.bawahan && user.bawahan.length > 0" class="flex flex-col items-center">
            
            <div class="h-8 border-l-2 border-gray-300"></div>

            <div class="flex justify-center">
                <div v-for="(child, index) in user.bawahan" :key="child.nik" class="flex flex-col items-center relative px-4">
                    
                    <div class="w-full h-8 relative -mt-px">
                        <div class="absolute left-1/2 -translate-x-1/2 h-full border-l-2 border-gray-300"></div>
                        <template v-if="user.bawahan.length > 1">
                            <div v-if="index === 0" class="absolute top-0 right-0 w-1/2 border-t-2 border-gray-300"></div>
                            <div v-else-if="index === user.bawahan.length - 1" class="absolute top-0 left-0 w-1/2 border-t-2 border-gray-300"></div>
                            <div v-else class="absolute top-0 left-0 w-full border-t-2 border-gray-300"></div>
                        </template>
                    </div>

                    <OrgNode :user="child" />
                </div>
            </div>
        </div>
    </div>
</template>
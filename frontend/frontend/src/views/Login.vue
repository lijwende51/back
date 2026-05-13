<template>
    <div class="users">
        <h1>Users</h1>
        <ul>
        <li v-for="user in users" :key="user.id">
            {{ user.name }} ({{ user.email }})
        </li>
        </ul>
    </div>
</template>
<script>
export default {
    data() {
        return {
            users: []
        }
    },
    created() {
        // Fetch users from API or other source
        this.users = [
            { id: 1, name: 'John Doe', email: 'john.doe@example.com' },
            { id: 2, name: 'Jane Smith', email: 'jane.smith@example.com' }
        ];
    }
}
</script>
<style scoped>
.users {
    padding: 20px;
}
.users h1 {
    margin-bottom: 20px;
}
.users ul {
    list-style-type: none;
    padding: 0;
}
.users li {
    margin-bottom: 10px;
}


</style>
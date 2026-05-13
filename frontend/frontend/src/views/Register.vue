<template>  
    <div class="register">
        <h1>Register</h1>
        <form @submit.prevent="handleSubmit">
            <div>
                <label for="name">Name:</label>
                <input type="text" id="name" v-model="name" required />
            </div>
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" v-model="email" required />
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" v-model="password" required />
            </div>
            <button type="submit">Register</button>
        </form>
    </div>
</template>

<script>
export default {
    data() {
        return {
            name: '',
            email: '',
            password: ''
        }
    },
    methods: {
        handleSubmit() {
            // Handle form submission logic here
        }
    }
}
</script>
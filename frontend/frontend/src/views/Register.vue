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

                // Handle registration logic here, e.g., send data to API
                console.log('Name:', this.name);
                console.log('Email:', this.email);
                console.log('Password:', this.password);

        }
        
    }
}
</script>
<style scoped>
.register {
    padding: 20px;
}
.register h1 {
    margin-bottom: 20px;
}
.register form {
    display: flex;
    flex-direction: column;
}   
.register form div {
    margin-bottom: 15px;
}
.register form label {
    margin-bottom: 5px;
    font-size: 14px;
    
}
.register form input {
    padding: 8px;
    font-size: 16px;
}
.register form button {
    padding: 10px;
    font-size: 16px;
    background-color: #007BFF;
    color: white;
    border: none;
    cursor: pointer;
}
.register form button:hover {
    background-color: #0056b3;
}
</style>
<x-guest-layout>
    <div class="w-full max-w-md bg-white-800 rounded-sm shadow-lg p-10 flex flex-col items-center">
        <img src="{{ asset('logo-fellie-florist.png') }}" alt="Logo"
            class="w-24 h-24 bg-white rounded-full p-2 shadow-lg scale-90 mb-4">
        <h1 class="text-1xl font-bold text-rose mb-1 tracking-tight ">FELLIE FLORIST</h1>
        <form method="POST" action="{{ route('login') }}" class="w-full space-y-6">
            @csrf
            <div>
                <label for="email" class="block text-center text-[#666666] text-bold mb-1">Username</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-rose-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </span>
                    <input id="email" name="email" type="email" required autofocus autocomplete="username"
                        placeholder="Type your username"
                        class="pl-10 pr-4 py-2 w-full border border-[#f2527d] rounded-lg focus:outline-none focus:ring-2 focus:ring-black text-black bg-[#f285a2] placeholder-black-400"
                        value="{{ old('email') }}" />
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <label for="password" class="block text-center text-[#666666] text-bold mb-1">Password</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-rose-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 11c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm0 2c-2.21 0-4 1.79-4 4v1h8v-1c0-2.21-1.79-4-4-4z" />
                        </svg>
                    </span>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                        placeholder="Type your password"
                        class="pl-10 pr-4 py-2 w-full border border-[#f2527d] rounded-lg focus:outline-none focus:ring-2 focus:ring-black text-black bg-[f285a2] placeholder-black-400" />
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <button type="submit"
                class="w-full py-3 bg-rose-500 rounded-lg shadow-lg font-bold text-white">LOGIN</button>
            <div style="margin-top: 16px; text-align: center; color: #555; font-size: 14px;">
                Login hanya untuk staf Fellie Florist.<br>
                Pelanggan bisa langsung berbelanja tanpa perlu login
            </div>
        </form>
        {{-- <div class="mt-2">
            <span class="text-xs text-gray-400">Or Sign Up with </span>
            <a href="#" class="text-xs font-semibold text-black hover:underline">Sign Up</a>
        </div> --}}
    </div>
</x-guest-layout>
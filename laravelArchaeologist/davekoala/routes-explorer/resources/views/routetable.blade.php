<table>
                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th>URI</th>
                                    <th>Name</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($routes as $route)
                                <tr>
                                    <td>
                                        @foreach(explode('|', $route['method']) as $method)
                                            @if($method !== 'HEAD')
                                                <span class="method method-{{ $method }}">{{ $method }}</span>
                                            @endif
                                        @endforeach
                                    </td>
                                    <td><code>{{ $route['uri'] }}</code></td>
                                    <td>{{ $route['name'] ?? '-' }}</td>
                                    <td>
                                        <button onclick="testRoute('{{ $route['method'] }}', '{{ $route['uri'] }}')" 
                                                class="test-btn">
                                            Explore
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>